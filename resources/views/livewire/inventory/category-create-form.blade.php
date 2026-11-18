<flux:modal name="create-category" class="md:w-[600px]">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <flux:heading size="lg">Inventory Category</flux:heading>
            <flux:text class="mt-1">
                Add a new inventory category and add its description.
            </flux:text>
        </div>

        {{-- Form --}}
        <form wire:submit="save" class="space-y-4">



            {{-- Category Name --}}
            <flux:input wire:model="form.name" label="Category Name" placeholder="Enter category name" required />


            <flux:textarea label="Description" placeholder="" wire:model="form.description" required />
            <flux:checkbox wire:model="form.is_active" label="Active" description="Item is available for use." />

            {{-- Footer --}}
            <div class="flex justify-end gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-700">

                <flux:modal.close>
                    <flux:button variant="ghost" type="button">
                        Cancel
                    </flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        Save Item
                    </span>

                    <span wire:loading wire:target="save">
                        Saving...
                    </span>
                </flux:button>

            </div>

        </form>
    </div>
</flux:modal>