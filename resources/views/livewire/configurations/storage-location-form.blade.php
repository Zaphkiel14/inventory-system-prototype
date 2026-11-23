<flux:modal name="storage-location-form" class="md:w-[500px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $form->storageLocation ? 'Edit Storage Location' : 'Add Storage Location' }}
            </flux:heading>
            <flux:text class="mt-1">Name the location and choose what kind of storage it is.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Name" placeholder="e.g. Shelf 1 - Ground Floor" required />

            <flux:select wire:model="form.type" label="Type" placeholder="Select a type" required>
                @foreach ($types as $type)
                    <flux:select.option value="{{ $type }}">{{ $type }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Save</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>
        </form>
    </div>
</flux:modal>
