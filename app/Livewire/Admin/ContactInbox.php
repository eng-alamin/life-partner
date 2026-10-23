<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ContactMessageStatus;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Messages from the public contact form, for staff with contact.handle.
 * "New" lists the oldest first; "handled" and "spam" list the most recently decided first.
 * The list shows the sender's name and the subject; the text and the email are read on the message page.
 */
#[Layout('layouts::admin')]
class ContactInbox extends Component
{
    use WithPagination;

    private const PER_PAGE = 15;

    private const TABS = ['new', 'handled', 'spam'];

    #[Url(as: 'tab')]
    public string $tab = 'new';

    public function mount(): void
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $this->normalizeTab();
    }

    public function setTab(string $tab): void
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $this->tab = $tab;
        $this->normalizeTab();
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('viewAny', ContactMessage::class);

        $this->normalizeTab();

        $query = ContactMessage::query()->select(['id', 'code', 'name', 'subject', 'status', 'created_at', 'handled_at']);

        if ($this->tab === ContactMessageStatus::New->value) {
            $query->where('status', ContactMessageStatus::New->value)->orderBy('created_at')->orderBy('id');
        } else {
            $query->where('status', $this->tab)->orderByDesc('handled_at')->orderByDesc('id');
        }

        return view('livewire.admin.contact-inbox', [
            'items' => $query->simplePaginate(self::PER_PAGE),
        ])->title(__('admin_contact.title'));
    }

    private function normalizeTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'new';
        }
    }
}
