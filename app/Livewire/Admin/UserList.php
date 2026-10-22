<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Accounts for an admin with users.manage. Search by name, email or biodata code; filter by
 * status and by staff. Only what is needed to find an account is shown: name, email,
 * status, staff role and biodata code. Phone numbers are never listed.
 */
#[Layout('layouts::admin')]
class UserList extends Component
{
    use WithPagination;

    private const PER_PAGE = 20;

    private const SEARCH_MAX = 100;

    private const STATUSES = ['all', 'active', 'suspended'];

    private const ROLES = ['all', 'staff'];

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $status = 'all';

    #[Url(as: 'role')]
    public string $role = 'all';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);

        $this->normalize();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('viewAny', User::class);

        $this->normalize();

        $query = User::query()
            ->select(['id', 'name', 'email', 'status', 'email_verified_at', 'created_at'])
            ->with(['biodata:id,user_id,public_code', 'roles:id,name']);

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->role === 'staff') {
            $query->whereHas('roles');
        }

        $term = $this->term();

        if ($term !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';

            $query->where(function (Builder $inner) use ($like, $term): void {
                $inner->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhereHas('biodata', fn (Builder $biodata) => $biodata->whereIn('public_code', [$term, mb_strtoupper($term)]));
            });
        }

        $items = $query->orderByDesc('created_at')->orderByDesc('id')->simplePaginate(self::PER_PAGE);

        $rows = $items->getCollection()->map(function (User $user): array {
            $roleName = $user->roles->first()?->name;

            return [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'role' => StaffRole::tryFrom((string) $roleName) !== null ? __('admin_users.roles.'.$roleName) : null,
                'code' => $user->biodata?->public_code,
                'verified' => $user->email_verified_at !== null,
                'joined' => $user->created_at?->format('Y-m-d'),
            ];
        })->all();

        return view('livewire.admin.user-list', [
            'items' => $items,
            'rows' => $rows,
        ])->title(__('admin_users.list.title'));
    }

    private function term(): string
    {
        $clean = preg_replace('/\p{Cc}+/u', ' ', $this->search);

        return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $clean) ?? ''), 0, self::SEARCH_MAX);
    }

    private function normalize(): void
    {
        if (! in_array($this->status, self::STATUSES, true)) {
            $this->status = 'all';
        }

        if (! in_array($this->role, self::ROLES, true)) {
            $this->role = 'all';
        }
    }
}
