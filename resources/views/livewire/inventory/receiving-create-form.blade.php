<flux:modal name="create-item" class="md:w-[600px]">
    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <flux:heading size="lg">Create Receiving Record</flux:heading>
            <flux:text class="mt-1">
                Add a new inventory item and configure its stock settings.
            </flux:text>
        </div>

        {{-- Form --}}
        <form wire:submit="save" class="space-y-4">


            <flux:select wire:model="Supplier" label="Supplier">
                <flux:select.option value="supplier ID">Supplier Name | Contact</flux:select.option>
            </flux:select>

            <flux:input wire:model="form.reference_no" label="Reference Number" placeholder="Ref No." required />

            <flux:input wire:model="form.received_date" label="Received Date" placeholder="DD/MM/YYYY" required />
           
            <flux:select wire:model="form.status" label="Delivery Status">
                <flux:select.option value="supplier ID">Fully Delivered</flux:select.option>
                <flux:select.option value="supplier ID">Partial Delivery</flux:select.option>
            </flux:select>

            <flux:textarea wire:model='form.remarks' label="Remarks"/>

            <flux:input type="file" wire:model="attachments" label="Attachments" multiple />
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