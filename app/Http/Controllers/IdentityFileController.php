<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\IdentityVerification;
use App\Services\Identity\IdentityFileStore;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Sends one identity picture to authorized staff. The files are encrypted on a private disk,
 * so this is the only way to see one. A missing request, a request staff may not open, a
 * request already decided and a missing file all give the same 404.
 * Every opening is written to the activity log with the request code only.
 */
final class IdentityFileController extends Controller
{
    public function show(Request $request, IdentityFileStore $files, string $verification, string $side): Response
    {
        $model = IdentityVerification::query()->where('code', $verification)->first();

        abort_if($model === null, 404);
        abort_unless(Gate::forUser($request->user())->allows('viewFile', $model), 404);

        $bytes = $files->read($side === 'back' ? $model->back_path : $model->front_path);

        abort_if($bytes === null, 404);

        activity('identity')
            ->causedBy($request->user())
            ->withProperties(['identity_code' => $model->code])
            ->log('identity.file_opened');

        return response($bytes, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
