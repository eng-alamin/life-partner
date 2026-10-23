<?php

declare(strict_types=1);

namespace App\Actions\Biodata;

use App\Enums\BiodataStatus;
use App\Enums\NotificationType;
use App\Models\Biodata;
use App\Models\User;
use App\Services\Biodata\BiodataProgress;
use App\Services\Notification\Notifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Sends a finished biodata (draft or rejected) to the admin approval queue.
 */
final class SubmitBiodata
{
    public function __construct(
        private readonly BiodataProgress $progress,
        private readonly Notifier $notifier,
    ) {}

    public function __invoke(User $user): Biodata
    {
        $submitted = DB::transaction(function () use ($user): Biodata {
            $biodata = Biodata::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($biodata === null) {
                throw ValidationException::withMessages(['form' => __('biodata.errors.incomplete')]);
            }

            Gate::forUser($user)->authorize('submit', $biodata);

            if ($this->progress->firstIncomplete($biodata, BiodataProgress::ALL_STEPS) !== null) {
                throw ValidationException::withMessages(['form' => __('biodata.errors.incomplete')]);
            }

            $biodata->forceFill([
                'status' => BiodataStatus::Pending->value,
                'submitted_at' => now(),
                'rejection_reason' => null,
            ])->save();

            return $biodata;
        });

        $this->notifier->toStaff(NotificationType::StaffBiodataWaiting);

        return $submitted;
    }
}
