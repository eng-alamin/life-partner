<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Reads and deletes the encrypted identity pictures on the private "identity_documents" disk.
 * Nothing here is public: the only reader is the authorized staff controller.
 */
final class IdentityFileStore
{
    public const DISK = 'identity_documents';

    /** The JPEG bytes, or null when the file is missing or cannot be decrypted. */
    public function read(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        try {
            $disk = Storage::disk(self::DISK);

            if (! $disk->exists($path)) {
                return null;
            }

            return Crypt::decryptString((string) $disk->get($path));
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Deletes the files. A failure is reported but never stops the caller: the database
     * decision is already saved, and the nightly cleanup can remove a file that is left.
     */
    public function delete(?string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }

            try {
                Storage::disk(self::DISK)->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
