<?php

use App\Enums\RequisitionStatus;
use App\Models\InventoryBatches;
use App\Models\Requisitions;
use App\Models\User;
use App\Services\Inventory\BatchCodeGenerator;

it('restarts the batch sequence when the date prefix changes to the next day', function () {
    $this->travelTo(now()->setDate(2026, 6, 15)->setTime(23, 58));
    $codes = app(BatchCodeGenerator::class);

    InventoryBatches::factory()->create(['batch_number' => $codes->batchNumber()]);
    $sameDay = $codes->batchNumber();

    $this->travelTo(now()->setDate(2026, 6, 16)->setTime(0, 1));

    expect($sameDay)->toBe('BAT-20260615-00002')
        ->and($codes->batchNumber())->toBe('BAT-20260616-00001');
});

it('restarts the request sequence when the month changes', function () {
    $this->travelTo(now()->setDate(2026, 6, 30)->setTime(23, 0));
    $codes = app(BatchCodeGenerator::class);

    Requisitions::create([
        'request_number' => $codes->requestNumber(),
        'user_id' => User::factory()->create()->id,
        'status' => RequisitionStatus::Pending,
        'purpose' => 'Monthly supplies',
        'submitted_at' => now(),
    ]);
    $sameMonth = $codes->requestNumber();

    $this->travelTo(now()->setDate(2026, 7, 1)->setTime(0, 30));

    expect(Requisitions::sole()->request_number)->toBe('REQ-202606-0001')
        ->and($sameMonth)->toBe('REQ-202606-0002')
        ->and($codes->requestNumber())->toBe('REQ-202607-0001');
});
