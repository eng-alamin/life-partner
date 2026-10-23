<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Actions\Account\BuildMemberExport;
use App\Actions\Account\ConfirmDataExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends the signed-in member's own data as a JSON file. Nothing is written to disk and the
 * link has no public address: it only works right after the password step (see ConfirmDataExport).
 */
final class DownloadMemberDataController extends Controller
{
    public function __invoke(Request $request, BuildMemberExport $build): StreamedResponse|RedirectResponse
    {
        $user = $request->user();

        if (! ConfirmDataExport::consumeGrant($user)) {
            return redirect()->route('account.export');
        }

        // Build everything first, so a failure shows an error page instead of a broken file.
        $json = json_encode(
            $build($user),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        RateLimiter::hit(ConfirmDataExport::limitKey($user), 86400);

        activity('account')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['action' => 'exported'])
            ->log('account.exported');

        return response()->streamDownload(
            static function () use ($json): void {
                echo $json;
            },
            'life-partner-my-data-'.now()->format('Ymd').'.json',
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Cache-Control' => 'no-store, private',
            ]
        );
    }
}
