<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Public welcome page: submit a supply request without an account, or track
 * one with its tracking code. Both tabs stay mounted so switching keeps state.
 */
#[Layout('layouts::guest')]
#[Title('Welcome')]
class PublicPortal extends Component
{
    public const TABS = ['request', 'track'];

    #[Url]
    public string $tab = 'request';

    public function mount(): void
    {
        // Links from notification emails carry ?code=, so open the tracker.
        if (filled(request()->query('code'))) {
            $this->tab = 'track';
        }

        $this->updatedTab();
    }

    public function updatedTab(): void
    {
        if (! in_array($this->tab, self::TABS, true)) {
            $this->tab = 'request';
        }
    }

    #[On('track-request')]
    public function showTracking(): void
    {
        $this->tab = 'track';
    }

    public function render()
    {
        return view('livewire.public-portal');
    }
}
