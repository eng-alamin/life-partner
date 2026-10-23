<?php

declare(strict_types=1);

namespace App\Actions\Identity;

use App\Enums\IdentityDocumentType;
use App\Enums\IdentityStatus;
use App\Enums\NotificationType;
use App\Models\Biodata;
use App\Models\IdentityVerification;
use App\Models\User;
use App\Services\Identity\IdentityImageProcessor;
use App\Services\Notification\Notifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * A member asks for the "identity verified" badge by sending a picture of a document.
 *
 * - Only the signed-in member's own request is ever created.
 * - No document number is read or stored. Only pictures.
 * - Each picture is decoded and saved again as a new JPEG, then encrypted with the app key
 *   and written to the private "identity_documents" disk under a random name.
 * - One open request per member (database unique key "open_key"), and a daily limit.
 * - Nothing about the document goes to the activity log except the request code.
 *
 * Staff review comes in a later module. Nothing is granted here.
 */
final class SubmitIdentityVerification
{
    private const DISK = 'identity_documents';

    public function __construct(
        private readonly IdentityImageProcessor $images,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user, string $documentType, UploadedFile $front, ?UploadedFile $back = null): IdentityVerification
    {
        $type = IdentityDocumentType::tryFrom($documentType);

        if ($type === null) {
            throw ValidationException::withMessages(['documentType' => __('identity.errors.type_invalid')]);
        }

        // Cheap early look, so a refused member does not cost any image work.
        $this->guard($user->getKey());

        // A passport has one page that matters. A back picture is ignored for it.
        $back = $type->hasBackSide() ? $back : null;

        $frontBytes = $this->images->process($front, 'front');
        $backBytes = $back !== null ? $this->images->process($back, 'back') : null;

        $paths = [];

        try {
            $frontPath = $this->store($frontBytes);
            $paths[] = $frontPath;

            $backPath = null;

            if ($backBytes !== null) {
                $backPath = $this->store($backBytes);
                $paths[] = $backPath;
            }

            $verification = DB::transaction(fn (): IdentityVerification => $this->create($user, $type, $frontPath, $backPath));

            // After the commit, so a rolled-back request tells nobody. A failure here never breaks the request.
            $this->notifier->toStaff(NotificationType::StaffIdentityWaiting);

            return $verification;
        } catch (UniqueConstraintViolationException) {
            $this->discard($paths);

            throw ValidationException::withMessages(['form' => __('identity.errors.already_open')]);
        } catch (Throwable $exception) {
            $this->discard($paths);

            throw $exception;
        }
    }

    private function create(User $user, IdentityDocumentType $type, string $frontPath, ?string $backPath): IdentityVerification
    {
        // The member row is locked, so two requests from the same member run one after the other.
        $this->guard($user->getKey(), lock: true);

        $verification = new IdentityVerification;
        $verification->forceFill([
            'user_id' => $user->getKey(),
            'status' => IdentityStatus::Pending->value,
            'document_type' => $type->value,
            'front_path' => $frontPath,
            'back_path' => $backPath,
            'open_key' => $user->getKey(),
            'submitted_at' => now(),
        ])->save();

        activity('identity')
            ->causedBy($user)
            ->withProperties(['identity_code' => $verification->code])
            ->log('identity.submitted');

        return $verification;
    }

    /**
     * Reads the member fresh from the database and refuses when a rule is broken.
     *
     * @throws ValidationException
     */
    private function guard(int|string $userId, bool $lock = false): void
    {
        $query = User::query()->whereKey($userId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $user = $query->firstOrFail();

        if (! $user->isActive()) {
            throw $this->refuse('account_closed');
        }

        if ($user->hasDeletionRequested()) {
            throw $this->refuse('deletion_waiting');
        }

        $biodata = Biodata::query()->where('user_id', $user->getKey())->first(['id', 'user_id', 'identity_verified_at']);

        if ($biodata === null) {
            throw $this->refuse('no_biodata');
        }

        if ($biodata->isIdentityVerified()) {
            throw $this->refuse('already_verified');
        }

        $open = IdentityVerification::query()
            ->where('user_id', $user->getKey())
            ->where('status', IdentityStatus::Pending->value)
            ->exists();

        if ($open) {
            throw $this->refuse('already_open');
        }

        $today = IdentityVerification::query()
            ->where('user_id', $user->getKey())
            ->where('submitted_at', '>=', now()->subDay())
            ->count();

        if ($today >= (int) config('lifepartner.identity.max_per_day')) {
            throw $this->refuse('daily_limit');
        }
    }

    private function refuse(string $key): ValidationException
    {
        return ValidationException::withMessages(['form' => __('identity.errors.'.$key)]);
    }

    /** Encrypts the bytes with the app key and stores them under a random name. */
    private function store(string $bytes): string
    {
        $name = Str::lower(Str::random(40)).'.enc';

        Storage::disk(self::DISK)->put($name, Crypt::encryptString($bytes));

        return $name;
    }

    /** @param  list<string>  $paths */
    private function discard(array $paths): void
    {
        foreach ($paths as $path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
