<?php

declare(strict_types=1);

namespace App\Services\Photo;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Turns an upload into two brand-new JPEG files. The original bytes are never
 * stored: the image is decoded and encoded again, so metadata (EXIF, GPS) and
 * anything hidden inside the file are dropped. Only real JPEG, PNG and WebP
 * images are accepted, judged by their content and not by the file name.
 */
final class ImageProcessor
{
    public const LARGE_MAX = 1200;

    public const THUMB_WIDTH = 360;

    public const THUMB_HEIGHT = 450;

    public const QUALITY = 85;

    /** Protects server memory: decoding needs about 4 bytes per pixel. */
    public const MAX_PIXELS = 16_000_000;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * @return array{large: string, thumb: string} JPEG bytes
     *
     * @throws ValidationException
     */
    public function process(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_file($path)) {
            throw $this->invalid();
        }

        $info = @getimagesize($path);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw $this->invalid();
        }

        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['upload' => __('photos.errors.too_large_pixels')]);
        }

        try {
            $large = $this->manager->read($path)
                ->scaleDown(self::LARGE_MAX, self::LARGE_MAX)
                ->toJpeg(self::QUALITY)
                ->toString();

            $thumb = $this->manager->read($path)
                ->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT)
                ->toJpeg(self::QUALITY)
                ->toString();
        } catch (Throwable) {
            throw $this->invalid();
        }

        return ['large' => $large, 'thumb' => $thumb];
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages(['upload' => __('photos.errors.unreadable')]);
    }
}
