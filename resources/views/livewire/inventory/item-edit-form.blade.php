<flux:modal name="edit-item" class="md:w-[600px]">
    <div class="space-y-6">

        <div>
            <flux:heading size="lg">Edit Inventory Item</flux:heading>
            <flux:text class="mt-1">
                Update this item's details and stock settings.
            </flux:text>
        </div>

        <form wire:submit="save" class="space-y-4">

            <flux:input wire:model="form.item_code" label="Item Code" placeholder="e.g. MED-001" required />

            <flux:input wire:model="form.name" label="Item Name" placeholder="Enter item name" required />

            {{-- Categories --}}
            <x-pillbox wire:model="form.category_ids" label="Categories" placeholder="Select categories..."
                :options="$this->categories->pluck('name', 'id')" />
            <flux:input wire:model="form.unit" label="Unit" placeholder="e.g. Pieces, Boxes" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="form.minimum_stock" type="number" label="Minimum Stock" min="0" required />
                <flux:input wire:model="form.reorder_level" type="number" label="Reorder Level" min="0" required />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <flux:checkbox wire:model="form.requires_expiry" label="Requires Expiry"
                    description="Item has an expiration date." />
                <flux:checkbox wire:model="form.is_active" label="Active" description="Item is available for use." />
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">Cancel</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">Update Item</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </flux:button>
            </div>

        </form>
    </div>
</flux:modal>