<?php

namespace App\Services\Inventory;

use App\Models\InventoryAdjustments;
use App\Models\InventoryTransactions;
use App\Models\StockIssuances;
use App\Models\StockReceipts;
use Illuminate\Support\Collection;

/**
 * Resolves the document (receipt, issuance, adjustment) behind ledger entries.
 */
class TransactionReferences
{
    /**
     * Look up the document behind each entry, one query per document type.
     * Keys are "{reference_type}:{reference_id}".
     *
     * @param  Collection<int, InventoryTransactions>  $transactions
     * @return array<string, array{label: string, detail: ?string}>
     */
    public function resolve(Collection $transactions): array
    {
        $ids = $transactions->groupBy('reference_type')->map(fn (Collection $g) => $g->pluck('reference_id')->unique()->values());
        $out = [];

        if ($receipts = $ids->get(StockReceipts::class)) {
            StockReceipts::with('supplier:id,name')->whereKey($receipts)->get()
                ->each(function (StockReceipts $r) use (&$out) {
                    $out[StockReceipts::class.':'.$r->id] = [
                        'label' => $r->reference_number,
                        'detail' => collect([$r->is_donation ? 'Donation' : null, $r->supplier?->name, $r->purchase_order_number ? 'PO '.$r->purchase_order_number : null])->filter()->join(' · '),
                    ];
                });
        }

        if ($issuances = $ids->get(StockIssuances::class)) {
            StockIssuances::with('requisition:id,request_number')->whereKey($issuances)->get()
                ->each(function (StockIssuances $i) use (&$out) {
                    $out[StockIssuances::class.':'.$i->id] = [
                        'label' => $i->issuance_number,
                        'detail' => collect([$i->requisition?->request_number, $i->received_by ? 'to '.$i->received_by : null])->filter()->join(' · '),
                    ];
                });
        }

        if ($adjustments = $ids->get(InventoryAdjustments::class)) {
            InventoryAdjustments::whereKey($adjustments)->get()
                ->each(function (InventoryAdjustments $a) use (&$out) {
                    $out[InventoryAdjustments::class.':'.$a->id] = [
                        'label' => $a->type?->label() ?? 'Adjustment',
                        'detail' => $a->reason,
                    ];
                });
        }

        return $out;
    }
}
