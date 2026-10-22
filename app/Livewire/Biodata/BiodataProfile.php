<?php

declare(strict_types=1);

namespace App\Livewire\Biodata;

use App\Models\Biodata;
use App\Services\Search\BiodataSearch;
use App\Services\Search\BiodataVisibility;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The full profile of one approved biodata, for signed-in members only.
 * Everything shown comes from BiodataVisibility. A biodata the viewer may not
 * see gives a 404, the same as a code that does not exist.
 */
#[Layout('layouts::app')]
class BiodataProfile extends Component
{
    #[Locked]
    public int $biodataId = 0;

    public function mount(Biodata $biodata, BiodataVisibility $visibility, BiodataSearch $search): void
    {
        abort_unless($visibility->canViewProfile(auth()->user(), $biodata, $search), 404);

        $this->biodataId = (int) $biodata->getKey();
    }

    public function render(BiodataVisibility $visibility, BiodataSearch $search)
    {
        // Loaded again on every render, so a biodata that was hidden or edited meanwhile disappears.
        $biodata = Biodata::query()
            ->with(['presentDistrict', 'educations', 'preference.preferredDistrict', 'photos'])
            ->findOrFail($this->biodataId);

        abort_unless($visibility->canViewProfile(auth()->user(), $biodata, $search), 404);

        return view('livewire.biodata.biodata-profile', [
            'code' => $biodata->public_code,
            'card' => $visibility->card($biodata),
            'sections' => $visibility->profile($biodata, auth()->user()),
            'educations' => $visibility->educations($biodata),
            'photos' => $visibility->profilePhotos(auth()->user(), $biodata),
            'isOwner' => auth()->id() === $biodata->user_id,
        ])->title(__('search.profile.title', ['code' => $biodata->public_code]));
    }
}
