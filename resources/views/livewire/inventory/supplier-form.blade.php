<flux:modal name="supplier-form" class="md:w-[560px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">
                {{ $form->supplier ? 'Edit Supplier' : 'Add Supplier' }}
            </flux:heading>
            <flux:text class="mt-1">Suppliers are picked when you receive stock.</flux:text>
        </div>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="form.name" label="Supplier name" placeholder="e.g. MedSupply Trading Corp." required />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.contact_person" label="Contact person" placeholder="e.g. Juan Dela Cruz" />
                <flux:input wire:model="form.contact" label="Contact number" placeholder="e.g. 0917 123 4567" />
            </div>

            <flux:input wire:model="form.email" type="email" label="Email" placeholder="orders@supplier.com" />

            <flux:input wire:model="form.address" label="Address" />

            <flux:textarea wire:model="form.notes" label="Notes" rows="2"
                placeholder="Payment terms, lead times, account numbers..." />

            <flux:switch wire:model="form.is_active" label="Active"
                description="Inactive suppliers can't be picked when receiving stock." />

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
