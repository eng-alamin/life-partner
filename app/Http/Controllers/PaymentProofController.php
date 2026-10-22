<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends a payment screenshot to its owner. The file is on a private disk, so this
 * is the only way to reach it. A missing payment, a payment of someone else and a
 * missing file all give the same 404, so nobody can tell whether a file exists.
 */
final class PaymentProofController extends Controller
{
    public function show(Request $request, string $payment): StreamedResponse
    {
        $model = Payment::query()->where('code', $payment)->first();

        abort_if($model === null, 404);
        abort_unless(Gate::forUser($request->user())->allows('viewProof', $model), 404);

        $disk = Storage::disk('payment_proofs');

        abort_unless($disk->exists($model->proof_path), 404);

        return $disk->response($model->proof_path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
