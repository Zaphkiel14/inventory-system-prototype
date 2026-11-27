<?php

namespace App\Livewire\Forms;

use App\Models\InventoryItems;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Form;

class InventoryItemsForm extends Form
{
    public ?InventoryItems $inventoryItem = null;

    /** @var array<int, int> */
    public array $category_ids = [];

    public string $item_code = '';

    public string $name = '';

    public string $unit = '';

    public int $minimum_stock = 0;

    public int $reorder_level = 0;

    public bool $requires_expiry = false;

    public bool $is_active = true;

    /** Equipment or semi-expendable tracked on ICS/PAR documents. */
    public bool $is_property = false;

    /** Default estimated useful life for this item's ICS/PAR lines (property items only). */
    public ?int $default_useful_life_years = null;

    public function setItem(InventoryItems $item): void
    {
        $this->inventoryItem = $item;

        $this->item_code = $item->item_code;
        $this->name = $item->name;
        $this->unit = $item->unit;
        $this->minimum_stock = (int) $item->minimum_stock;
        $this->reorder_level = (int) $item->reorder_level;
        $this->requires_expiry = (bool) $item->requires_expiry;
        $this->is_active = (bool) $item->is_active;
        $this->is_property = (bool) $item->is_property;
        $this->default_useful_life_years = $item->default_useful_life_years;

        $this->category_ids = $item->inventoryCategory()
            ->pluck('inventory_categories.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Every field's rules. item_code and name are unique in the table, so the
     * item being edited is ignored.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $ignoreCurrent = fn (string $column) => Rule::unique('inventory_items', $column)->ignore($this->inventoryItem?->id);

        return [
            'item_code' => ['required', 'string', 'max:255', $ignoreCurrent('item_code')],
            'name' => ['required', 'string', 'max:255', $ignoreCurrent('name')],
            'unit' => ['required', 'string', 'max:255'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'requires_expiry' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'is_property' => ['required', 'boolean'],
            'default_useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:inventory_categories,id'],
        ];
    }

    public function store(): void
    {
        $this->validate();

        DB::transaction(function () {
            $item = InventoryItems::create([
                'item_code' => $this->item_code,
                'name' => $this->name,
                'unit' => $this->unit,
                'minimum_stock' => $this->minimum_stock,
                'reorder_level' => $this->reorder_level,
                'requires_expiry' => $this->requires_expiry,
                'is_active' => $this->is_active,
                'is_property' => $this->is_property,
                'default_useful_life_years' => $this->usefulLife(),
            ]);

            // Attach selected categories through the pivot table.
            $item->inventoryCategory()->sync($this->category_ids);
        });

        $this->reset();
    }

    public function update(): void
    {
        $this->validate();

        $this->inventoryItem->update([
            'item_code' => $this->item_code,
            'name' => $this->name,
            'unit' => $this->unit,
            'minimum_stock' => $this->minimum_stock,
            'reorder_level' => $this->reorder_level,
            'requires_expiry' => $this->requires_expiry,
            'is_active' => $this->is_active,
            'is_property' => $this->is_property,
            'default_useful_life_years' => $this->usefulLife(),
        ]);

        // Update the item's category associations.
        $this->inventoryItem->inventoryCategory()->sync($this->category_ids);

        $this->reset();
    }

    /**
     * Useful life only applies to property items; consumables store none.
     */
    protected function usefulLife(): ?int
    {
        return $this->is_property ? $this->default_useful_life_years : null;
    }
}
