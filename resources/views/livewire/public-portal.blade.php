<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-8 sm:px-6 lg:py-12">
    <div class="text-center">
        <flux:heading size="xl" level="1">{{ __('Supply Requests') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Request items from inventory, or check on a request you already sent.') }}
        </flux:text>
    </div>

    {{-- Tabs (free Flux has no tabs component, so a button group switches them) --}}
    <div class="flex justify-center">
        <flux:button.group>
            <flux:button icon="clipboard-document-list" wire:click="$set('tab', 'request')"
                :variant="$tab === 'request' ? 'primary' : 'filled'" aria-controls="tab-request"
                :aria-pressed="$tab === 'request' ? 'true' : 'false'">
                {{ __('Submit a request') }}
            </flux:button>
            <flux:button icon="magnifying-glass" wire:click="$set('tab', 'track')"
                :variant="$tab === 'track' ? 'primary' : 'filled'" aria-controls="tab-track"
                :aria-pressed="$tab === 'track' ? 'true' : 'false'">
                {{ __('Track a request') }}
            </flux:button>
        </flux:button.group>
    </div>

    {{-- Both tabs stay mounted so switching back and forth keeps what was typed --}}
    <div id="tab-request" @class(['hidden' => $tab !== 'request'])>
        <livewire:portal.request-form />
    </div>

    <div id="tab-track" @class(['hidden' => $tab !== 'track'])>
        <livewire:portal.track-request />
    </div>
</div>
