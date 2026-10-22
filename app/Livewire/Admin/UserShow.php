<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\User\ChangeStaffRole;
use App\Actions\User\ReactivateUser;
use App\Actions\User\SuspendUser;
use App\Enums\StaffPermission;
use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One account. The id is locked (the browser cannot change it) and every action is checked
 * again on the server inside its action class. Contact numbers are not shown: staff see
 * only whether the phone is verified.
 */
#[Layout('layouts::admin')]
class UserShow extends Component
{
    #[Locked]
    public int $userId = 0;

    public string $reason = '';

    public function mount(User $user): void
    {
        Gate::authorize('view', $user);

        $this->userId = (int) $user->getKey();
    }

    public function suspend(SuspendUser $action): void
    {
        $this->begin();

        $action(auth()->user(), $this->userId, $this->reason);

        $this->finish('suspended');
    }

    public function reactivate(ReactivateUser $action): void
    {
        $this->begin();

        $action(auth()->user(), $this->userId);

        $this->finish('reactivated');
    }

    public function makeModerator(ChangeStaffRole $action): void
    {
        $this->begin();

        $action->makeModerator(auth()->user(), $this->userId);

        $this->finish('moderator_granted');
    }

    public function removeModerator(ChangeStaffRole $action): void
    {
        $this->begin();

        $action->removeModerator(auth()->user(), $this->userId);

        $this->finish('moderator_removed');
    }

    public function render()
    {
        $user = User::query()
            ->with(['biodata:id,user_id,public_code,status', 'roles:id,name'])
            ->findOrFail($this->userId);

        Gate::authorize('view', $user);

        $roleName = $user->roles->first()?->name;
        $viewer = auth()->user();

        return view('livewire.admin.user-show', [
            'user' => $user,
            'roleLabel' => StaffRole::tryFrom((string) $roleName) !== null ? __('admin_users.roles.'.$roleName) : null,
            'isAdminRole' => $roleName === StaffRole::Admin->value,
            'isSelf' => $viewer?->is($user) ?? false,
            'canSuspend' => Gate::allows('suspend', $user),
            'canReactivate' => Gate::allows('reactivate', $user),
            'canMakeModerator' => Gate::allows('makeModerator', $user),
            'canRemoveModerator' => Gate::allows('removeModerator', $user),
            'canReviewBiodata' => $viewer?->can(StaffPermission::ReviewBiodata->value) ?? false,
        ])->title(__('admin_users.show.title'));
    }

    private function begin(): void
    {
        $this->resetErrorBag();

        $key = 'user-admin:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            throw ValidationException::withMessages([
                'form' => __('account.throttled_minutes', ['minutes' => max(1, (int) ceil(RateLimiter::availableIn($key) / 60))]),
            ]);
        }

        RateLimiter::hit($key, 3600);
    }

    private function finish(string $outcome): void
    {
        session()->flash('status', __('admin_users.flash.'.$outcome));
        $this->redirectRoute('admin.users.show', $this->userId);
    }
}
