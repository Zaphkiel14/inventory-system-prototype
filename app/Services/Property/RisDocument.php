<?php

namespace App\Services\Property;

use App\Models\InventoryItems;
use App\Models\IssuedPropertyUnits;
use App\Models\Requisitions;
use App\Support\DocumentLogo;
use App\Support\PropertySettings;
use Carbon\CarbonInterface;

/**
 * Builds the view data for the RIS (Appendix 63) printout of a requisition.
 * The keys are the contract resources/views/pdf/ris.blade.php is built on.
 *
 * Stock Available is derived from system stock, never entered: "Yes" when
 * something was released, or when usable stock covers what is still
 * outstanding.
 */
class RisDocument
{
    /**
     * @return array{
     *     entity_name: string, entity_address: string, logo: ?string,
     *     fund_cluster: ?string, division: ?string, office: ?string, rc_code: ?string,
     *     ris_number: ?string, ris_date: ?CarbonInterface, purpose: string,
     *     lines: array<int, array{stock_no: string, unit: string, description: string, quantity: int, stock_available: string, issue_qty: int, remarks: ?string, units: array<int, array{brand: ?string, model: ?string, serial_number: ?string}>}>,
     *     requested_by: array{name: ?string, designation: ?string, date: ?CarbonInterface},
     *     approved_by: array{name: ?string, designation: ?string, date: ?CarbonInterface},
     *     issued_by: array{name: ?string, designation: ?string, date: ?CarbonInterface},
     *     received_by: array{name: ?string, designation: ?string, date: ?CarbonInterface},
     *     paper_size: string,
     * }
     */
    public static function for(Requisitions $requisition): array
    {
        $requisition->loadMissing([
            'items' => fn ($query) => $query->orderBy('id'),
            'items.inventoryItem',
            'stockIssuances' => fn ($query) => $query->latest('released_at')->latest('id'),
        ]);

        $available = InventoryItems::query()
            ->whereKey($requisition->items->pluck('inventory_item_id'))
            ->withAvailableQty()
            ->pluck('available_qty', 'id');

        $units = IssuedPropertyUnits::query()
            ->with('stockIssuanceBatch.stockIssuanceItem')
            ->whereHas('stockIssuanceBatch.stockIssuanceItem', fn ($query) => $query
                ->whereIn('requisition_item_id', $requisition->items->modelKeys()))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (IssuedPropertyUnits $unit) => $unit->stockIssuanceBatch->stockIssuanceItem->requisition_item_id);

        $lines = $requisition->items->map(fn ($line) => [
            'stock_no' => (string) $line->inventoryItem?->item_code,
            'unit' => (string) $line->inventoryItem?->unit,
            'description' => (string) $line->inventoryItem?->name,
            'quantity' => (int) $line->requested_qty,
            'stock_available' => self::stockAvailable(
                (int) $line->released_qty,
                (int) $line->outstanding_qty,
                (int) ($available[$line->inventory_item_id] ?? 0),
            ),
            'issue_qty' => (int) $line->released_qty,
            'remarks' => $line->issue_remarks,
            'units' => $units->get($line->id, collect())
                ->map(fn (IssuedPropertyUnits $unit) => [
                    'brand' => $unit->brand,
                    'model' => $unit->model,
                    'serial_number' => $unit->serial_number,
                ])
                ->values()
                ->all(),
        ])->values()->all();

        $latestIssuance = $requisition->stockIssuances->first();

        return [
            'entity_name' => PropertySettings::entityName(),
            'entity_address' => PropertySettings::entityAddress(),
            'logo' => DocumentLogo::dataUri(),
            'fund_cluster' => $requisition->fund_cluster,
            'division' => $requisition->division,
            'office' => $requisition->office,
            'rc_code' => $requisition->rc_code,
            'ris_number' => $requisition->ris_number,
            'ris_date' => $requisition->approved_on ?? $requisition->submitted_at,
            'purpose' => (string) $requisition->purpose,
            'lines' => $lines,
            'requested_by' => [
                'name' => $requisition->requester_name,
                'designation' => $requisition->requester_position,
                'date' => $requisition->submitted_at,
            ],
            'approved_by' => [
                'name' => $requisition->approved_by_name,
                'designation' => $requisition->approved_by_designation,
                'date' => $requisition->approved_on,
            ],
            'issued_by' => [
                'name' => $latestIssuance?->released_by,
                'designation' => $latestIssuance?->released_by_designation,
                'date' => $latestIssuance?->released_at,
            ],
            'received_by' => [
                'name' => $latestIssuance?->received_by,
                'designation' => $latestIssuance?->received_by_designation,
                'date' => $latestIssuance?->released_at,
            ],
            'paper_size' => PropertySettings::paperSize()->value,
        ];
    }

    /**
     * "Yes" if anything was released, or if usable stock covers a non-zero
     * outstanding quantity; otherwise "No".
     */
    public static function stockAvailable(int $releasedQty, int $outstandingQty, int $usableQty): string
    {
        return $releasedQty > 0 || ($outstandingQty > 0 && $usableQty >= $outstandingQty) ? 'Yes' : 'No';
    }
}
