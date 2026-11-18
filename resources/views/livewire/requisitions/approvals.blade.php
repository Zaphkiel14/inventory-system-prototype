<flux:card variant="default">

    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            <flux:heading size="xl" level="2">Requisition Approvals</flux:heading>
            <flux:text class="mt-1">Manage Approvals and allocations</flux:text>
        </div>

        <div class="flex items-center gap-2">


            <flux:input class="w-64" icon="magnifying-glass" size="sm" placeholder="Search request"
                wire:model.live.debounce.500ms="search" />
            <flux:modal.trigger name="create-item">
                <flux:button size="sm" icon="plus">Add Request</flux:button>
            </flux:modal.trigger>
            @livewire('inventory.receiving-create-form')
            @livewire('inventory.item-edit-form')

        </div>
    </div>

</flux:card>