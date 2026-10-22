<?php

declare(strict_types=1);

namespace App\Services\Identity;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Turns a picture of an identity document into one brand-new JPEG. The original bytes are
 * never stored: the image is decoded and encoded again, so EXIF data (such as the GPS place)
 * and anything hidden in the file are dropped. Only real JPEG, PNG and WebP images pass,
 * judged by content and not by the file name.
 */
final class IdentityImageProcessor
{
    /** Large enough to read small print on a document. */
    public const MAX_SIDE = 2000;

    public const QUALITY = 85;

    /** Protects server memory: decoding needs about 4 bytes per pixel. */
    public const MAX_PIXELS = 16_000_000;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * @param  string  $field  The form field name, so the error shows under the right input.
     * @return string JPEG bytes
     *
     * @throws ValidationException
     */
    public function process(UploadedFile $file, string $field): string
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            throw $this->invalid($field);
        }

        $info = @getimagesize($path);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw $this->invalid($field);
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages([$field => __('identity.errors.pixels')]);
        }

        try {
            return $this->manager->read($path)
                ->scaleDown(self::MAX_SIDE, self::MAX_SIDE)
                ->toJpeg(self::QUALITY)
                ->toString();
        } catch (Throwable) {
            throw $this->invalid($field);
        }
    }

    private function invalid(string $field): ValidationException
    {
        return ValidationException::withMessages([$field => __('identity.errors.unreadable')]);
    }
}
