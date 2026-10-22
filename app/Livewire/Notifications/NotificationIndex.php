<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use App\Models\User;
use App\Services\Notification\NotificationPresenter;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The signed-in member's own notifications, newest first. Every action looks the
 * notification up inside the member's own rows, so someone else's id finds nothing (404).
 * "Open" marks the row read and goes to the page only if the member may still use it.
 */
#[Layout('layouts::app')]
class NotificationIndex extends Component
{
    use WithPagination;

    private const PER_PAGE = 20;

    private const FILTERS = ['all', 'unread'];

    #[Url(as: 'filter', except: 'all')]
    public string $filter = 'all';

    public function mount(): void
    {
        $this->normalizeFilter();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->normalizeFilter();
        $this->resetPage();
    }

    public function open(NotificationPresenter $presenter, string $id): void
    {
        $user = $this->user();
        $notification = $this->owned($user)->whereKey($id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = $presenter->urlFor($notification, $user);

        if ($url !== null) {
            $this->redirect($url);
        }
    }

    public function markRead(string $id): void
    {
        $notification = $this->owned($this->user())->whereKey($id)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllRead(): void
    {
        $this->owned($this->user())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render(NotificationPresenter $presenter)
    {
        $this->normalizeFilter();

        $user = $this->user();

        $items = $this->owned($user)
            ->when($this->filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('created_at')
            ->simplePaginate(self::PER_PAGE);

        $rows = $items->getCollection()
            ->map(fn (DatabaseNotification $notification): array => $presenter->present($notification))
            ->all();

        $unread = $this->owned($user)->whereNull('read_at')->count();

        return view('livewire.notifications.notification-index', [
            'items' => $items,
            'rows' => $rows,
            'unread' => $unread,
        ])->title(__('notifications.page.title'));
    }

    /** Only this member's own rows. */
    private function owned(User $user)
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey());
    }

    private function user(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function normalizeFilter(): void
    {
        if (! in_array($this->filter, self::FILTERS, true)) {
            $this->filter = 'all';
        }
    }
}
