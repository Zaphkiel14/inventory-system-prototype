<?php

namespace App\Livewire\Forms;

use App\Models\Suppliers;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SuppliersForm extends Form
{
    public ?Suppliers $supplier = null;

    public string $name = '';

    public string $contact_person = '';

    public string $contact = '';

    public string $email = '';

    public string $address = '';

    public string $notes = '';

    public bool $is_active = true;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($this->supplier?->id)],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'contact_person' => 'contact person',
            'contact' => 'contact number',
        ];
    }

    public function load(Suppliers $supplier): void
    {
        $this->supplier = $supplier;
        $this->name = $supplier->name;
        $this->contact_person = (string) $supplier->contact_person;
        $this->contact = (string) $supplier->contact;
        $this->email = (string) $supplier->email;
        $this->address = (string) $supplier->address;
        $this->notes = (string) $supplier->notes;
        $this->is_active = $supplier->is_active;
    }

    public function store(): Suppliers
    {
        $this->validate();

        $supplier = Suppliers::create($this->attributes());

        $this->reset();

        return $supplier;
    }

    public function update(): void
    {
        $this->validate();

        $this->supplier->update($this->attributes());

        $this->reset();
    }

    /**
     * @return array<string, mixed>
     */
    protected function attributes(): array
    {
        return [
            'name' => trim($this->name),
            'contact_person' => $this->nullable($this->contact_person),
            'contact' => $this->nullable($this->contact),
            'email' => $this->nullable($this->email),
            'address' => $this->nullable($this->address),
            'notes' => $this->nullable($this->notes),
            'is_active' => $this->is_active,
        ];
    }

    protected function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
