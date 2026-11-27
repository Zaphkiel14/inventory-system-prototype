<?php

namespace App\Livewire\Forms;

use App\Enums\RequisitionStatus;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Notifications\RequisitionReceived;
use App\Services\AdministrativeAreas\AdministrativeAreaService;
use App\Services\Inventory\BatchCodeGenerator;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Throwable;

/**
 * Submits a requisition request (from the public welcome page, so the
 * requester may not have an account). A request only records what is wanted:
 * approval decides how much is dispensed and issuance releases it, so this
 * form never touches stock.
 */
class RequisitionsForm extends Form
{
    public string $requester_name = '';

    public string $supervisor_name = '';

    /** Optional position/designation of the requester (RIS "Requested by"). */
    public string $requester_position = '';

    public string $requester_email = '';

    /** @var int|string|null administrative area id from the API */
    public $administrative_area_id = '';

    public string $purpose = '';

    /** @var array<int, array{inventory_item_id: mixed, requested_qty: mixed}> */
    public array $items = [];

    public function initialize(): void
    {
        $this->reset();
        $this->resetValidation();
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'inventory_item_id' => '',
            'requested_qty' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function rules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:255'],
            'supervisor_name' => ['required', 'string', 'max:255'],
            'requester_position' => ['nullable', 'string', 'max:255'],
            'administrative_area_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) {
                    try {
                        $area = app(AdministrativeAreaService::class)->find((int) $value);
                    } catch (Throwable) {
                        $fail('Designations could not be loaded. Please try again later.');

                        return;
                    }

                    if ($area === null) {
                        $fail('Select a valid designation.');
                    }
                },
            ],
            'requester_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'purpose' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'distinct',
                Rule::exists('inventory_items', 'id')->where('is_active', true),
            ],
            'items.*.requested_qty' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'items.*.inventory_item_id.distinct' => 'This item is already on another line.',
        ];
    }

    protected function validationAttributes(): array
    {
        $attributes = [
            'requester_name' => 'your name',
            'supervisor_name' => "immediate supervisor's name",
            'requester_email' => 'email address',
            'requester_position' => 'position',
            'administrative_area_id' => 'designation',
        ];

        foreach ($this->items as $i => $_) {
            $n = $i + 1;
            $attributes["items.$i.inventory_item_id"] = "item (line $n)";
            $attributes["items.$i.requested_qty"] = "quantity (line $n)";
        }

        return $attributes;
    }

    public function validate($rules = null, $messages = [], $attributes = [])
    {
        // Pasted addresses often carry stray spaces, which the email rule rejects.
        $this->requester_email = trim($this->requester_email);

        return parent::validate($rules, $messages, $attributes);
    }

    public function store(): Requisitions
    {
        $this->validate();

        $codes = app(BatchCodeGenerator::class);
        $areas = app(AdministrativeAreaService::class);
        $area = $areas->find((int) $this->administrative_area_id);
        $division = $areas->divisionOf($area);

        $email = strtolower(trim($this->requester_email)) ?: null;

        $requisition = DB::transaction(function () use ($codes, $areas, $area, $division, $email) {
            // Snapshot what was on hand at submission so approvers can see
            // the stock picture the requester saw.
            $available = InventoryItems::query()
                ->whereKey(collect($this->items)->pluck('inventory_item_id'))
                ->withAvailableQty()
                ->pluck('available_qty', 'id');

            $requisition = Requisitions::create([
                'request_number' => $codes->requestNumber(),
                'tracking_code' => $codes->trackingCode(),
                'user_id' => Auth::id(),
                'requester_name' => trim($this->requester_name),
                'supervisor_name' => trim($this->supervisor_name),
                'requester_email' => $email,
                'administrative_area_id' => $area['id'],
                'designation' => $areas->label($area),
                'requester_position' => filled($this->requester_position) ? trim($this->requester_position) : null,
                'office_area_id' => $area['id'],
                'office' => $areas->shortLabel($area),
                'division_area_id' => $division['id'],
                'division' => $areas->shortLabel($division),
                'status' => RequisitionStatus::Pending,
                'purpose' => trim($this->purpose),
                'submitted_at' => now(),
            ]);

            foreach ($this->items as $row) {
                $requisition->items()->create([
                    'inventory_item_id' => $row['inventory_item_id'],
                    'requested_qty' => (int) $row['requested_qty'],
                    'available_qty' => (int) ($available[$row['inventory_item_id']] ?? 0),
                    'approved_qty' => null,
                    'released_qty' => 0,
                    'outstanding_qty' => 0,
                ]);
            }

            return $requisition;
        });

        if ($email !== null) {
            Notification::route('mail', $email)->notify(new RequisitionReceived($requisition));
        }

        return $requisition;
    }
}
