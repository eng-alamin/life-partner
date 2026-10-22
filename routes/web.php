<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Middleware\PrivateLinkHeaders;
use App\Livewire\Account\DeleteAccount;
use App\Livewire\Account\IdentityVerificationPage;
use App\Livewire\Auth\CompleteSocialSignup;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmailNotice;
use App\Livewire\Admin\ActivityLogList;
use App\Livewire\Admin\ApprovalQueue;
use App\Livewire\Admin\ApprovalReview;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DeletionList;
use App\Livewire\Admin\HiddenQueue;
use App\Livewire\Admin\PageForm;
use App\Livewire\Admin\PageList;
use App\Livewire\Admin\PaymentQueue;
use App\Livewire\Admin\PaymentReview;
use App\Livewire\Admin\PlanForm;
use App\Livewire\Admin\PlanList;
use App\Livewire\Admin\PhotoQueue;
use App\Livewire\Admin\ReportQueue;
use App\Livewire\Admin\ReportReview;
use App\Livewire\Admin\SettingsForm;
use App\Livewire\Admin\UserList;
use App\Livewire\Admin\UserShow;
use App\Livewire\Biodata\BiodataIndex;
use App\Livewire\Biodata\BiodataProfile;
use App\Livewire\Biodata\BiodataWizard;
use App\Livewire\Biodata\MyBiodata;
use App\Livewire\Billing\BillingIndex;
use App\Livewire\Billing\PaymentPage;
use App\Livewire\Billing\PlanIndex;
use App\Livewire\Block\MyBlocks;
use App\Livewire\Interest\GuardianDecision;
use App\Livewire\Interest\InterestInbox;
use App\Livewire\Notifications\NotificationIndex;
use App\Livewire\Notifications\NotificationSettings;
use App\Livewire\Shortlist\ShortlistIndex;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');

// Content pages (about, FAQ, terms ...). The list of slugs is fixed in code; an unpublished page is a 404.
foreach (\App\Models\Page::SLUGS as $pageSlug) {
    Route::get('/p/'.$pageSlug, [PageController::class, 'show'])
        ->defaults('slug', $pageSlug)
        ->middleware('throttle:120,1')
        ->name('pages.'.$pageSlug);
}

// Public search: guests see cards only, with fewer filters (enforced on the server).
Route::livewire('/biodatas', BiodataIndex::class)->name('biodatas.index');

// One-time guardian link: no login, the secret is in the address (see PrivateLinkHeaders).
Route::livewire('/guardian/{token}', GuardianDecision::class)
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware(['throttle:60,1', PrivateLinkHeaders::class])
    ->name('guardian.decide');

// Plans and prices are public. Buying needs a login (checked on the server).
Route::livewire('/plans', PlanIndex::class)->name('plans.index');

Route::post('/locale', [LocaleController::class, 'update'])
    ->middleware('throttle:20,1')
    ->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/register', Register::class)->name('register');
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/forgot-password', ForgotPassword::class)->name('password.request');

    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->middleware('throttle:20,1')
        ->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->middleware('throttle:20,1')
        ->name('social.callback');
    Route::livewire('/auth/complete', CompleteSocialSignup::class)->name('social.complete');
});

Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::livewire('/email/verify', VerifyEmailNotice::class)->name('verification.notice');

    Route::post('/logout', LogoutController::class)
        ->middleware('throttle:20,1')
        ->name('logout');

    Route::middleware('email.verified')->group(function (): void {
        Route::view('/dashboard', 'pages.dashboard')->name('dashboard');
        Route::livewire('/my-biodata', MyBiodata::class)->name('my-biodata');
        Route::livewire('/my-biodata/edit', BiodataWizard::class)->name('my-biodata.edit');

        // The member asks to delete their own account (14-day wait, can be cancelled).
        Route::livewire('/account/delete', DeleteAccount::class)->name('account.delete');

        // The member sends a picture of an identity document to get the "identity verified" badge.
        Route::livewire('/account/verification', IdentityVerificationPage::class)->name('account.verification');

        // Interests the member received and sent.
        Route::livewire('/interests', InterestInbox::class)->name('interests.index');

        // The member's own notifications and email settings.
        Route::livewire('/notifications', NotificationIndex::class)->name('notifications.index');
        Route::livewire('/notifications/settings', NotificationSettings::class)->name('notifications.settings');

        // Members the signed-in member has blocked.
        Route::livewire('/blocks', MyBlocks::class)->name('blocks.index');

        // The member's own shortlist and ignore list.
        Route::livewire('/shortlist', ShortlistIndex::class)->name('shortlist.index');

        // The member's credits, membership and payments.
        Route::livewire('/billing', BillingIndex::class)->name('billing.index');
        Route::livewire('/billing/pay/{payment}', PaymentPage::class)
            ->where('payment', '[a-z0-9]{24}')
            ->name('billing.pay');

        // Payment screenshot: private disk, owner only (see PaymentProofController).
        Route::get('/billing/proof/{payment}', [PaymentProofController::class, 'show'])
            ->where('payment', '[a-z0-9]{24}')
            ->middleware('throttle:60,1')
            ->name('billing.proof');

        // Full profile of another member's approved biodata (members only).
        Route::livewire('/biodatas/{biodata}', BiodataProfile::class)->name('biodatas.show');

        // Photo files: private disk, checked on every request (see PhotoController).
        Route::get('/photos/{biodata}/{photo}/{size}', [PhotoController::class, 'show'])
            ->where(['biodata' => '[A-Za-z0-9]{6,20}', 'photo' => '[a-z0-9]{24}', 'size' => 'thumb|large'])
            ->middleware('throttle:300,1')
            ->name('photos.show');

        // Staff area. Every page re-checks its own permission as well.
        Route::middleware(['can:admin.access', 'throttle:120,1'])
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::livewire('/', Dashboard::class)->name('dashboard');
                Route::livewire('/approvals', ApprovalQueue::class)->name('approvals');
                Route::livewire('/approvals/{biodata}', ApprovalReview::class)->name('approvals.show');
                Route::livewire('/hidden', HiddenQueue::class)->name('hidden');
                Route::livewire('/photos', PhotoQueue::class)->name('photos');
                Route::livewire('/reports', ReportQueue::class)->name('reports');
                Route::livewire('/reports/{report}', ReportReview::class)->name('reports.show');
                Route::livewire('/plans', PlanList::class)->name('plans');
                Route::livewire('/plans/create', PlanForm::class)->name('plans.create');
                Route::livewire('/plans/{plan}/edit', PlanForm::class)
                    ->where('plan', '[a-z0-9]+(?:-[a-z0-9]+)*')
                    ->name('plans.edit');
                Route::livewire('/users', UserList::class)->name('users');
                Route::livewire('/users/{user}', UserShow::class)
                    ->where('user', '[0-9]+')
                    ->name('users.show');
                Route::livewire('/deletions', DeletionList::class)->name('deletions');
                Route::livewire('/activity-log', ActivityLogList::class)->name('activity-log');
                Route::livewire('/content', PageList::class)->name('content');
                Route::livewire('/content/{slug}/edit', PageForm::class)
                    ->where('slug', '[a-z]+(?:-[a-z]+)*')
                    ->name('content.edit');
                Route::livewire('/settings', SettingsForm::class)->name('settings');
                Route::livewire('/payments', PaymentQueue::class)->name('payments');
                Route::livewire('/payments/{payment}', PaymentReview::class)
                    ->where('payment', '[a-z0-9]{24}')
                    ->name('payments.show');
            });
    });
});
