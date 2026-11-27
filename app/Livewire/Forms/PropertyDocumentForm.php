<?php

namespace App\Livewire\Forms;

use App\Enums\AuditAction;
use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentStatus;
use App\Enums\PropertyDocumentType;
use App\Events\PropertyDocumentFinalized;
use App\Models\AuditLogs;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Services\Property\PropertyClassifier;
use App\Support\AuditContext;
use App\Support\Money;
use App\Support\PropertySettings;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Form;

/**
 * Edits one ICS or PAR. Drafts save with light checks; finalize() runs the
 * full rules and locks the document (status Active, renewal date set).
 * After finalizing, fields can still be corrected with a reason, but lines
 * can't be added or removed. Every write is audited (Auditable).
 *
 * Custody records only: nothing here moves stock.
 */
class PropertyDocumentForm extends Form
{
    /** Statuses an Active document can move to, each with a reason. */
    public const STATUS_TARGETS = [
        PropertyDocumentStatus::Returned,
        PropertyDocumentStatus::Transferred,
        PropertyDocumentStatus::Renewed,
        PropertyDocumentStatus::Disposed,
    ];

    /** Set by initialize()/load() only. */
    #[Locked]
    public ?int $document_id = null;

    /** PropertyDocumentType value; set by initialize()/load() only. */
    #[Locked]
    public string $type = '';

    /** PropertyClass value (splv/sphv) for an ICS, '' for a PAR; set by initialize()/load() only. */
    #[Locked]
    public string $ics_class = '';

    public string $document_number = '';

    public string $fund_cluster = '';

    /** Y-m-d */
    public string $document_date = '';

    public string $iar_number = '';

    public string $from_name = '';

    public string $from_position = '';

    /** Y-m-d */
    public string $from_date = '';

    public string $to_name = '';

    public string $to_position = '';

    /** Y-m-d */
    public string $to_date = '';

    /**
     * @var array<int, array{id: ?int, issued_property_unit_id: ?int, inventory_item_id: ?int, quantity: mixed, unit: ?string, unit_cost: mixed, item_name: ?string, item_type: ?string, brand: ?string, model: ?string, serial_number: ?string, property_number: ?string, estimated_useful_life_years: mixed, date_acquired: ?string}>
     */
    public array $lines = [];

    /** Needed (min 5) when document_number doesn't match its pattern; audited. */
    public string $pattern_override_reason = '';

    /** Needed (min 5) to save changes to a finalized document; audited. */
    public string $change_reason = '';

    /**
     * A new hand-encoded document (no source issuance), e.g. to load
     * existing property, starting with one blank line.
     */
    public function initialize(PropertyDocumentType $type, ?PropertyClass $icsClass = null): void
    {
        $this->reset();
        $this->resetValidation();

        $this->type = $type->value;
        $this->ics_class = $type === PropertyDocumentType::Ics ? ($icsClass ?? PropertyClass::Splv)->value : '';
        $this->document_date = today()->format('Y-m-d');

        $this->addLine();
    }

    public function load(PropertyDocuments $document): void
    {
        $this->reset();
        $this->resetValidation();

        $document->loadMissing(['lines' => fn ($query) => $query->orderBy('id')]);
        $prefix = PropertyDocumentLines::propertyNumberPrefix($document->ics_class);

        $this->document_id = $document->id;
        $this->type = $document->type->value;
        $this->ics_class = (string) $document->ics_class?->value;
        $this->document_number = (string) $document->document_number;
        $this->fund_cluster = (string) $document->fund_cluster;
        $this->document_date = $document->document_date->format('Y-m-d');
        $this->iar_number = (string) $document->iar_number;
        $this->from_name = (string) $document->from_name;
        $this->from_position = (string) $document->from_position;
        $this->from_date = (string) $document->from_date?->format('Y-m-d');
        $this->to_name = (string) $document->to_name;
        $this->to_position = (string) $document->to_position;
        $this->to_date = (string) $document->to_date?->format('Y-m-d');

        $this->lines = $document->lines->map(fn (PropertyDocumentLines $line) => [
            'id' => $line->id,
            'issued_property_unit_id' => $line->issued_property_unit_id,
            'inventory_item_id' => $line->inventory_item_id,
            'quantity' => $line->quantity,
            'unit' => $line->unit,
            'unit_cost' => (string) $line->unit_cost,
            'item_name' => $line->item_name,
            'item_type' => $line->item_type,
            'brand' => $line->brand,
            'model' => $line->model,
            'serial_number' => $line->serial_number,
            // Prefix shown in the form only; never stored on its own.
            'property_number' => filled($line->property_number) ? $line->property_number : $prefix,
            'estimated_useful_life_years' => $line->estimated_useful_life_years,
            'date_acquired' => $line->date_acquired?->format('Y-m-d'),
        ])->values()->all();
    }

    /**
     * Add a blank hand-encoded line (drafts only).
     */
    public function addLine(): void
    {
        $this->ensureDraft('form.lines', 'Lines can\'t be added after the document is finalized.');

        $this->lines[] = [
            'id' => null,
            'issued_property_unit_id' => null,
            'inventory_item_id' => null,
            'quantity' => 1,
            'unit' => '',
            'unit_cost' => '',
            'item_name' => '',
            'item_type' => '',
            'brand' => '',
            'model' => '',
            'serial_number' => '',
            'property_number' => PropertyDocumentLines::propertyNumberPrefix($this->icsClass()),
            'estimated_useful_life_years' => null,
            'date_acquired' => '',
        ];
    }

    /**
     * Remove a hand-encoded line (drafts only; issued units stay on their document).
     */
    public function removeLine(int $index): void
    {
        $this->ensureDraft('form.lines', 'Lines can\'t be removed after the document is finalized.');

        if (! isset($this->lines[$index])) {
            return;
        }

        if (filled($this->lines[$index]['issued_property_unit_id'] ?? null)) {
            throw ValidationException::withMessages([
                "form.lines.{$index}" => 'This line is an issued unit and stays on the document.',
            ]);
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    /**
     * Save. A draft gets light checks; a finalized document gets the full
     * rules plus a required change reason, and keeps its lines.
     */
    public function store(): PropertyDocuments
    {
        $existing = $this->document();
        $this->enforceIssuedLines($existing);

        if ($existing?->isFinalized()) {
            $this->validateFull($existing, changing: true);

            return DB::transaction(fn () => AuditContext::withReason($this->change_reason, function () use ($existing) {
                $document = $this->lockDocument($existing->id);
                $this->ensureSameLines($document);
                $this->write($document);
                $this->auditPatternOverride($document);

                return $document->refresh();
            }));
        }

        $this->validateDraft();

        return DB::transaction(function () use ($existing) {
            $document = $existing ? $this->lockDocument($existing->id) : null;

            if ($document?->isFinalized()) {
                throw ValidationException::withMessages(['form.document_id' => 'This document was finalized in the meantime. Reopen it to make changes.']);
            }

            return $this->write($document);
        });
    }

    /**
     * Run the full rules, save, and lock the document: status Active,
     * finalized_at now, renewal due after the renewal period. Fires
     * PropertyDocumentFinalized once the transaction commits.
     */
    public function finalize(): PropertyDocuments
    {
        $existing = $this->document();
        $this->enforceIssuedLines($existing);

        if ($existing?->isFinalized()) {
            throw ValidationException::withMessages(['form.document_id' => 'This document is already finalized.']);
        }

        $this->validateFull($existing, changing: false);

        $document = DB::transaction(function () use ($existing) {
            $document = $existing ? $this->lockDocument($existing->id) : null;

            if ($document?->isFinalized()) {
                throw ValidationException::withMessages(['form.document_id' => 'This document is already finalized.']);
            }

            $document = $this->write($document);
            $this->auditPatternOverride($document);

            AuditContext::as(AuditAction::Finalized, null, fn () => $document->update([
                'status' => PropertyDocumentStatus::Active,
                'finalized_at' => now(),
                'renewal_due_on' => self::renewalDueOn($document->document_date)->toDateString(),
            ]));

            PropertyDocumentFinalized::dispatch($document);

            return $document;
        });

        $this->load($document->refresh());

        return $document;
    }

    /**
     * Delete a draft. Each line is deleted through Eloquent first, so every
     * line gets its own audit row (the FK cascade is only a safety net);
     * issued units on it become available to the drafter again.
     */
    public function deleteDraft(): void
    {
        DB::transaction(function (): void {
            $document = $this->lockDocument((int) $this->document_id);

            if ($document->isFinalized()) {
                throw ValidationException::withMessages(['form.document_id' => 'Finalized documents cannot be deleted. Change their status instead.']);
            }

            $document->lines()->get()->each(fn (PropertyDocumentLines $line) => $line->delete());
            $document->delete();
        });

        $this->reset();
    }

    /**
     * Move an Active document to Returned, Transferred, Renewed or Disposed.
     */
    public function changeStatus(PropertyDocumentStatus $to, string $reason): PropertyDocuments
    {
        $this->change_reason = $reason;

        $this->validate(['change_reason' => ['required', 'string', 'min:5', 'max:1000']], attributes: ['change_reason' => 'reason']);

        if (! in_array($to, self::STATUS_TARGETS, true)) {
            throw ValidationException::withMessages(['form.status' => 'A document can only be marked Returned, Transferred, Renewed or Disposed.']);
        }

        $document = DB::transaction(function () use ($to, $reason) {
            $document = $this->lockDocument((int) $this->document_id);

            if ($document->status !== PropertyDocumentStatus::Active) {
                throw ValidationException::withMessages(['form.status' => 'Only an active document can change status.']);
            }

            AuditContext::as(AuditAction::StatusChanged, $reason, fn () => $document->update(['status' => $to]));

            return $document;
        });

        $this->change_reason = '';

        return $document;
    }

    /**
     * The cost class of a line ("splv"/"sphv"/"ppe"), or null when its cost
     * isn't a valid amount yet. Used for the live badge.
     */
    public function lineClass(int $index): ?PropertyClass
    {
        $cost = $this->lines[$index]['unit_cost'] ?? null;

        return is_numeric($cost) && (float) $cost > 0 ? app(PropertyClassifier::class)->classifyCost((string) $cost) : null;
    }

    public function documentType(): PropertyDocumentType
    {
        return PropertyDocumentType::from($this->type);
    }

    public function icsClass(): ?PropertyClass
    {
        return PropertyClass::tryFrom($this->ics_class);
    }

    public function document(): ?PropertyDocuments
    {
        return $this->document_id ? PropertyDocuments::query()->find($this->document_id) : null;
    }

    /**
     * Whether document_number is filled in but doesn't match its pattern
     * (saving then needs an override reason).
     */
    public function patternMismatch(): bool
    {
        if (blank($this->document_number)) {
            return false;
        }

        $pattern = $this->documentType() === PropertyDocumentType::Ics
            ? PropertySettings::icsPattern()
            : PropertySettings::parPattern();

        return ! PropertySettings::matchesPattern($pattern, trim($this->document_number));
    }

    /*
     * Validation
     */

    protected function validateDraft(): void
    {
        $this->validate([
            ...$this->headerRules(full: false),
            ...$this->lineRules(full: false),
        ], attributes: $this->attributeNames());
    }

    protected function validateFull(?PropertyDocuments $document, bool $changing): void
    {
        $rules = [
            ...$this->headerRules(full: true),
            ...$this->lineRules(full: true),
        ];

        if ($changing) {
            $rules['change_reason'] = ['required', 'string', 'min:5', 'max:1000'];
        }

        $this->validate($rules, attributes: $this->attributeNames());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function headerRules(bool $full): array
    {
        $required = $full ? 'required' : 'nullable';
        $type = $this->documentType();

        return [
            'type' => ['required', Rule::enum(PropertyDocumentType::class)],
            'ics_class' => $type === PropertyDocumentType::Ics
                ? ['required', Rule::in([PropertyClass::Splv->value, PropertyClass::Sphv->value])]
                : ['nullable', 'prohibited'],
            'document_number' => [
                $required, 'string', 'max:255',
                Rule::unique('property_documents', 'document_number')
                    ->where('type', $type->value)
                    ->ignore($this->document_id),
                ...($full ? [$this->matchesPattern()] : []),
                ...($full && $type === PropertyDocumentType::Ics ? [$this->matchesIcsPrefix()] : []),
            ],
            'pattern_override_reason' => ['nullable', 'string', 'min:5', 'max:1000'],
            'fund_cluster' => [$required, 'string', 'max:255'],
            'document_date' => ['required', 'date'],
            'iar_number' => ['nullable', 'string', 'max:255'],
            'from_name' => [$required, 'string', 'max:255'],
            'from_position' => [$required, 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_name' => [$required, 'string', 'max:255'],
            'to_position' => [$required, 'string', 'max:255'],
            'to_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Rules per line index (so each line's property number ignores its own row).
     *
     * @return array<string, array<int, mixed>>
     */
    protected function lineRules(bool $full): array
    {
        $rules = ['lines' => ['required', 'array', 'min:1']];
        $isIcs = $this->documentType() === PropertyDocumentType::Ics;

        foreach ($this->lines as $i => $line) {
            $rules["lines.{$i}.item_name"] = ['required', 'string', 'max:255'];
            $rules["lines.{$i}.unit"] = ['required', 'string', 'max:50'];
            $rules["lines.{$i}.unit_cost"] = [
                'required', 'numeric', 'decimal:0,2', $full ? 'gt:0' : 'min:0', 'max:9999999999.99',
                ...($full ? [$this->costFitsDocument()] : []),
            ];
            $rules["lines.{$i}.quantity"] = ['required', 'integer', 'min:1', 'max:1000000'];
            $rules["lines.{$i}.item_type"] = ['nullable', 'string', 'max:255'];
            $rules["lines.{$i}.brand"] = ['nullable', 'string', 'max:255'];
            $rules["lines.{$i}.model"] = ['nullable', 'string', 'max:255'];
            $rules["lines.{$i}.serial_number"] = [
                'nullable', 'string', 'max:255',
                ...($full ? [$this->serialMeansSingleUnit($i)] : []),
            ];
            $rules["lines.{$i}.property_number"] = [
                $full ? 'required' : 'nullable', 'string', 'max:255',
                ...($full ? [$this->notBarePrefix()] : []),
                $this->uniquePropertyNumber($i),
            ];
            $rules["lines.{$i}.estimated_useful_life_years"] = [$full && $isIcs ? 'required' : 'nullable', 'integer', 'min:1', 'max:100'];
            $rules["lines.{$i}.date_acquired"] = [$full ? 'required' : 'nullable', 'date'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function attributeNames(): array
    {
        $names = [
            'document_number' => $this->documentType() === PropertyDocumentType::Ics ? 'ICS No.' : 'PAR No.',
            'pattern_override_reason' => 'override reason',
            'fund_cluster' => 'fund cluster',
            'document_date' => 'date',
            'iar_number' => 'IAR No.',
            'from_name' => $this->documentType() === PropertyDocumentType::Ics ? 'received from (name)' : 'issued by (name)',
            'from_position' => $this->documentType() === PropertyDocumentType::Ics ? 'received from (position)' : 'issued by (position)',
            'to_name' => 'received by (name)',
            'to_position' => 'received by (position)',
            'change_reason' => 'reason for the change',
        ];

        foreach ($this->lines as $i => $_) {
            $n = $i + 1;

            foreach ([
                'item_name' => 'description', 'unit' => 'unit', 'unit_cost' => 'unit cost', 'quantity' => 'quantity',
                'serial_number' => 'serial number', 'property_number' => $this->documentType() === PropertyDocumentType::Ics ? 'inventory item no.' : 'property no.',
                'estimated_useful_life_years' => 'estimated useful life', 'date_acquired' => 'date acquired',
            ] as $field => $label) {
                $names["lines.{$i}.{$field}"] = "{$label} (line {$n})";
            }
        }

        return $names;
    }

    private function matchesPattern(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->patternMismatch() && blank($this->pattern_override_reason)) {
                $pattern = $this->documentType() === PropertyDocumentType::Ics ? PropertySettings::icsPattern() : PropertySettings::parPattern();
                $fail("The :attribute does not match the expected format ({$pattern}). Check it, or give an override reason to save it anyway.");
            }
        };
    }

    /**
     * An ICS number's SPLV/SPHV prefix must match the slip's class.
     */
    private function matchesIcsPrefix(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $class = $this->icsClass();

            if (! is_string($value) || $class === null || preg_match('/^(SPLV|SPHV)\b/i', trim($value), $matches) !== 1) {
                return;
            }

            if (strtolower($matches[1]) !== $class->value) {
                $fail("This is an {$this->classCode($class)} ICS, so its number must start with {$this->classCode($class)}, not ".strtoupper($matches[1]).'.');
            }
        };
    }

    /**
     * ICS lines must match the slip's class and stay below the PPE threshold;
     * PAR lines must be at or above it.
     */
    private function costFitsDocument(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_numeric($value) || (float) $value <= 0) {
                return;
            }

            $class = app(PropertyClassifier::class)->classifyCost((string) $value);
            $amount = '₱'.number_format((float) $value, 2);
            $ppe = '₱'.number_format((float) PropertySettings::ppeThreshold(), 2);

            if ($this->documentType() === PropertyDocumentType::Par) {
                if ($class !== PropertyClass::Ppe) {
                    $fail("{$amount} is below the PPE threshold ({$ppe}), so it is {$this->classCode($class)}. Record it on an ICS instead.");
                }

                return;
            }

            if ($class === PropertyClass::Ppe) {
                $fail("{$amount} is at or above the PPE threshold ({$ppe}). Record it on a PAR instead.");
            } elseif ($class !== $this->icsClass()) {
                $fail("{$amount} is {$this->classCode($class)}, but this is an {$this->classCode($this->icsClass())} ICS. Put it on an {$this->classCode($class)} ICS.");
            }
        };
    }

    private function serialMeansSingleUnit(int $index): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($index): void {
            if (filled($value) && (int) ($this->lines[$index]['quantity'] ?? 0) !== 1) {
                $fail('A line with a serial number must have a quantity of 1.');
            }
        };
    }

    private function notBarePrefix(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (is_string($value) && in_array(strtoupper(trim($value)), ['LV', 'HV'], true)) {
                $fail('Complete the :attribute; it is only the prefix.');
            }
        };
    }

    /**
     * Unique across every ICS and PAR line (ignoring this line's own row) and
     * not repeated on this document. A bare LV/HV prefix counts as empty.
     */
    private function uniquePropertyNumber(int $index): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($index): void {
            $number = $this->storedPropertyNumber($value);

            if ($number === null) {
                return;
            }

            $repeats = collect($this->lines)
                ->filter(fn (array $line, int $i) => $i !== $index && $this->storedPropertyNumber($line['property_number'] ?? null) !== null
                    && mb_strtolower((string) $this->storedPropertyNumber($line['property_number'])) === mb_strtolower($number));

            if ($repeats->isNotEmpty()) {
                $fail("The :attribute {$number} is used on another line of this document.");

                return;
            }

            $taken = PropertyDocumentLines::query()
                ->whereRaw('lower(property_number) = ?', [mb_strtolower($number)])
                ->when($this->lines[$index]['id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
                ->exists();

            if ($taken) {
                $fail("The :attribute {$number} is already on another ICS or PAR.");
            }
        };
    }

    /*
     * Writing
     */

    /**
     * Create or update the document and sync its lines. Hand-encoded lines
     * removed from the form are deleted; issued-unit lines are never removed.
     */
    /**
     * Replace whatever the client posted for issuance lines with the server's
     * values, before validating or saving:
     *  - a line's unit link (issued_property_unit_id, inventory_item_id) is
     *    taken from the saved row with that line id on this document, never
     *    from the post; any other line is hand-encoded (no unit);
     *  - a line with an issued unit gets quantity 1, the item's unit and the
     *    unit's recorded cost (so its class can't be changed from the browser).
     * Hand-encoded lines stay fully editable. One query for the saved lines
     * with their units and items.
     */
    protected function enforceIssuedLines(?PropertyDocuments $document): void
    {
        if ($document !== null) {
            $this->type = $document->type->value;
            $this->ics_class = (string) $document->ics_class?->value;
        }

        $saved = $document === null
            ? collect()
            : $document->lines()->with('issuedPropertyUnit.inventoryItem')->get()->keyBy('id');

        foreach ($this->lines as $i => $line) {
            $row = filled($line['id'] ?? null) ? $saved->get((int) $line['id']) : null;

            if ($row === null) {
                // Unknown or foreign line id: a new hand-encoded line.
                $this->lines[$i]['id'] = null;
                $this->lines[$i]['issued_property_unit_id'] = null;
                $this->lines[$i]['inventory_item_id'] = null;

                continue;
            }

            $this->lines[$i]['issued_property_unit_id'] = $row->issued_property_unit_id;
            $this->lines[$i]['inventory_item_id'] = $row->inventory_item_id;

            $unit = $row->issuedPropertyUnit;

            if ($unit !== null) {
                $this->lines[$i]['quantity'] = 1;
                $this->lines[$i]['unit'] = $unit->inventoryItem->unit ?? $row->unit;
                $this->lines[$i]['unit_cost'] = (string) $unit->unit_cost;
            }
        }
    }

    protected function write(?PropertyDocuments $document): PropertyDocuments
    {
        $header = [
            'document_number' => $this->clean($this->document_number),
            'fund_cluster' => $this->clean($this->fund_cluster),
            'document_date' => $this->document_date,
            'iar_number' => $this->clean($this->iar_number),
            'from_name' => $this->clean($this->from_name),
            'from_position' => $this->clean($this->from_position),
            'from_date' => $this->clean($this->from_date),
            'to_name' => $this->clean($this->to_name),
            'to_position' => $this->clean($this->to_position),
            'to_date' => $this->clean($this->to_date),
        ];

        if ($document === null) {
            $document = PropertyDocuments::create([
                'type' => $this->documentType(),
                'ics_class' => $this->documentType() === PropertyDocumentType::Ics ? $this->icsClass() : null,
                ...$header,
            ]);
        } else {
            $document->update($header);
        }

        $existing = $document->lines()->get()->keyBy('id');
        $kept = [];

        foreach ($this->lines as $line) {
            $values = [
                'quantity' => (int) $line['quantity'],
                'unit' => $this->clean($line['unit'] ?? null),
                'unit_cost' => Money::fromCentavos(Money::centavos((string) $line['unit_cost'])),
                'item_name' => trim((string) $line['item_name']),
                'item_type' => $this->clean($line['item_type'] ?? null),
                'brand' => $this->clean($line['brand'] ?? null),
                'model' => $this->clean($line['model'] ?? null),
                'serial_number' => $this->clean($line['serial_number'] ?? null),
                'property_number' => $this->storedPropertyNumber($line['property_number'] ?? null),
                'estimated_useful_life_years' => filled($line['estimated_useful_life_years'] ?? null) ? (int) $line['estimated_useful_life_years'] : null,
                'date_acquired' => $this->clean($line['date_acquired'] ?? null),
            ];

            $row = filled($line['id'] ?? null) ? $existing->get((int) $line['id']) : null;

            if ($row !== null) {
                $row->update($values);
                $kept[] = $row->id;
            } else {
                // Only hand-encoded lines are created here; issued units come from the drafter.
                $kept[] = $document->lines()->create($values)->id;
            }
        }

        $existing
            ->reject(fn (PropertyDocumentLines $line) => in_array($line->id, $kept, true) || $line->issued_property_unit_id !== null)
            ->each(fn (PropertyDocumentLines $line) => $line->delete());

        $this->document_id = $document->id;

        return $document;
    }

    protected function auditPatternOverride(PropertyDocuments $document): void
    {
        if ($this->patternMismatch() && filled($this->pattern_override_reason)) {
            AuditLogs::record(
                AuditAction::DocumentPatternOverride,
                $document,
                [],
                ['document_number' => $document->document_number],
                $this->pattern_override_reason,
            );
        }
    }

    /**
     * After finalizing, the form must still hold exactly the document's lines.
     */
    protected function ensureSameLines(PropertyDocuments $document): void
    {
        $stored = $document->lines()->pluck('id')->sort()->values()->all();
        $form = collect($this->lines)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($stored !== $form) {
            throw ValidationException::withMessages(['form.lines' => 'Lines can\'t be added or removed after the document is finalized.']);
        }
    }

    protected function lockDocument(int $id): PropertyDocuments
    {
        return PropertyDocuments::query()->lockForUpdate()->findOrFail($id);
    }

    protected function ensureDraft(string $key, string $message): void
    {
        if ($this->document()?->isFinalized()) {
            throw ValidationException::withMessages([$key => $message]);
        }
    }

    /**
     * The value stored for a property number: trimmed, and null when empty
     * or only the LV/HV prefix.
     */
    protected function storedPropertyNumber(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value === null || in_array(strtoupper($value), ['LV', 'HV'], true) ? null : $value;
    }

    protected function clean(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    private function classCode(?PropertyClass $class): string
    {
        return $class === null ? '' : strtoupper($class->value);
    }

    /**
     * Renewal due date for a document date (used by finalize()).
     */
    public static function renewalDueOn(CarbonImmutable $documentDate): CarbonImmutable
    {
        return $documentDate->addYears(PropertySettings::renewalYears());
    }
}
