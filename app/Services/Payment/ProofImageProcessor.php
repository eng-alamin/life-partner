<?php

declare(strict_types=1);

namespace App\Services\Payment;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Turns a payment screenshot into one brand-new JPEG. The original bytes are never
 * stored: the image is decoded and encoded again, so metadata and anything hidden in
 * the file are dropped. Only real JPEG, PNG and WebP images pass, judged by content.
 */
final class ProofImageProcessor
{
    public const MAX_SIDE = 1600;

    public const QUALITY = 80;

    /** Protects server memory: decoding needs about 4 bytes per pixel. */
    public const MAX_PIXELS = 16_000_000;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * @return string JPEG bytes
     *
     * @throws ValidationException
     */
    public function process(UploadedFile $file): string
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
            throw ValidationException::withMessages(['proof' => __('billing.errors.proof_pixels')]);
        }

        try {
            return $this->manager->read($path)
                ->scaleDown(self::MAX_SIDE, self::MAX_SIDE)
                ->toJpeg(self::QUALITY)
                ->toString();
        } catch (Throwable) {
            throw $this->invalid();
        }
    }

    private function invalid(): ValidationException
    {
        return ValidationException::withMessages(['proof' => __('billing.errors.proof_unreadable')]);
    }
}
