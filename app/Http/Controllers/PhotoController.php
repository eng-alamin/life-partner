<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BiodataPhoto;
use App\Services\Search\BiodataSearch;
use App\Services\Search\BiodataVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends a photo file after checking, on every request, that this person may
 * see it. Photos live on a private disk, so there is no other way to reach
 * them. Anything not allowed or not found gives the same 404, so nobody can
 * tell whether a photo exists.
 */
final class PhotoController extends Controller
{
    public function show(
        Request $request,
        string $biodata,
        string $photo,
        string $size,
        BiodataVisibility $visibility,
        BiodataSearch $search,
    ): StreamedResponse {
        $model = BiodataPhoto::query()->with('biodata')->where('code', $photo)->first();

        abort_if($model === null || $model->biodata === null || $model->biodata->public_code !== $biodata, 404);
        abort_unless($visibility->canViewPhoto($request->user(), $model, $search), 404);

        $path = $size === 'thumb' ? $model->thumb_path : $model->path;
        $disk = Storage::disk('photos');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
