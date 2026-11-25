<?php

use App\Enums\InventoryTransactionType;
use App\Enums\ReceiptStatus;
use App\Livewire\Inventory\Receiving;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Models\InventoryBatches;
use App\Models\InventoryItems;
use App\Models\InventoryTransactions;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Services\Inventory\DeliveryProgress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

const PARTIAL_MESSAGE_ATTACH = 'Attach at least one file (delivery receipt, invoice or deed of donation).';

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->supplier = Suppliers::factory()->create();
    $this->location = StorageLocations::factory()->create();
    $this->itemA = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true]);
    $this->itemB = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true]);
    $this->itemC = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true]);
});

function pdPdf(string $name = 'dr.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 50, 'application/pdf');
}

function pdRow(InventoryItems $item, int|string $ordered, int|string $qty, array $extra = []): array
{
    return array_merge([
        'inventory_item_id' => $item->id,
        'storage_location_id' => test()->location->id,
        'batch_number' => '',
        'lot_number' => '',
        'ordered_qty' => $ordered,
        'quantity' => $qty,
        'unit_cost' => 5,
        'manufactured_date' => null,
        'expiration_date' => null,
    ], $extra);
}

function pdRef(): string
{
    static $n = 0;

    return 'DR-PD-'.++$n;
}

/** A Livewire form for a new partial delivery, not yet saved. */
function pdPartialForm(?array $rows = null, bool $withFile = true): Testable
{
    $rows ??= [
        pdRow(test()->itemA, 100, 40),
        pdRow(test()->itemB, 50, 0, ['storage_location_id' => '', 'unit_cost' => '']),
    ];

    $form = Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', test()->supplier->id)
        ->set('form.reference_number', pdRef())
        ->set('form.status', ReceiptStatus::Partial->value)
        ->set('form.items', $rows);

    return $withFile ? $form->set('attachments', [pdPdf()]) : $form;
}

/** Records a partial root: A ordered 100 (40 received), B ordered 50 (0 received). */
function pdRoot(): StockReceipts
{
    pdPartialForm()->call('save')->assertHasNoErrors();

    return StockReceipts::query()->whereNull('parent_receipt_id')->latest('id')->firstOrFail();
}

/** Opens the follow-up modal for a root, ready to fill in. */
function pdFollowUp(StockReceipts $root): Testable
{
    return Livewire::test(ReceivingCreateForm::class)
        ->call('followUp', $root->id)
        ->set('form.reference_number', pdRef());
}

describe('attachments on a new receipt', function () {
    it('requires at least one attachment', function () {
        $form = pdPartialForm(withFile: false)->call('save')->assertHasErrors(['attachments' => 'required']);

        expect($form->errors()->get('attachments'))->toContain(PARTIAL_MESSAGE_ATTACH)
            ->and(StockReceipts::count())->toBe(0);
    });

    it('rejects a disallowed file type', function (string $name, string $mime) {
        pdPartialForm(withFile: false)
            ->set('attachments', [UploadedFile::fake()->create($name, 10, $mime)])
            ->call('save')
            ->assertHasErrors(['attachments.0']);

        expect(StockReceipts::count())->toBe(0);
    })->with([
        'exe' => ['virus.exe', 'application/x-msdownload'],
        'txt' => ['notes.txt', 'text/plain'],
    ]);

    it('rejects a file over 5 MB', function () {
        pdPartialForm(withFile: false)
            ->set('attachments', [UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf')])
            ->call('save')
            ->assertHasErrors(['attachments.0']);
    });

    it('accepts a pdf and stores it on the receipt', function () {
        $root = pdRoot();

        expect($root->attachments)->toHaveCount(1);
        Storage::disk('public')->assertExists($root->attachments->first()->path);
    });

    it('reports field errors before the missing attachment', function () {
        pdPartialForm(withFile: false)
            ->set('form.reference_number', '')
            ->call('save')
            ->assertHasErrors(['form.reference_number'])
            ->assertHasNoErrors(['attachments']);
    });
});

describe('recording a new partial delivery', function () {
    it('stores ordered lines, batches only for received lines and keeps the root partial', function () {
        $root = pdRoot();

        expect($root->status)->toBe(ReceiptStatus::Partial)
            ->and($root->parent_receipt_id)->toBeNull()
            ->and($root->completed_at)->toBeNull()
            ->and($root->is_donation)->toBeFalse()
            ->and($root->lines->pluck('ordered_qty', 'inventory_item_id')->all())
            ->toEqual([$this->itemA->id => 100, $this->itemB->id => 50]);

        $batches = $root->inventoryBatches;
        expect($batches)->toHaveCount(1)
            ->and($batches->first()->inventory_item_id)->toBe($this->itemA->id)
            ->and($batches->first()->received_qty)->toBe(40);

        $ledger = InventoryTransactions::query()->where('type', InventoryTransactionType::Receipt)->get();
        expect($ledger)->toHaveCount(1)
            ->and($ledger->first()->quantity)->toBe(40)
            ->and($ledger->first()->balance_after)->toBe(40)
            ->and($ledger->first()->batch_id)->toBe($batches->first()->id)
            ->and($ledger->first()->reference_id)->toEqual($root->id);

        $progress = app(DeliveryProgress::class);
        expect($progress->outstanding($root))->toBe(110)
            ->and($progress->isComplete($root))->toBeFalse();
    });

    it('lets a zero-quantity line skip location, cost and expiry', function () {
        $expiring = InventoryItems::factory()->create(['requires_expiry' => true, 'is_active' => true]);

        pdPartialForm([
            pdRow($this->itemA, 10, 10),
            pdRow($expiring, 20, 0, ['storage_location_id' => '', 'unit_cost' => '', 'expiration_date' => null]),
        ])->call('save')->assertHasNoErrors();

        expect(InventoryBatches::count())->toBe(1);
    });

    it('still requires location, cost and expiry for a line that receives stock', function () {
        $expiring = InventoryItems::factory()->create(['requires_expiry' => true, 'is_active' => true]);

        pdPartialForm([pdRow($expiring, 20, 5, ['storage_location_id' => '', 'unit_cost' => '', 'expiration_date' => null])])
            ->call('save')
            ->assertHasErrors(['form.items.0.storage_location_id', 'form.items.0.unit_cost', 'form.items.0.expiration_date']);
    });

    it('rejects an ordered quantity below the quantity received now', function () {
        $form = pdPartialForm([pdRow($this->itemA, 5, 10)])->call('save')->assertHasErrors(['form.items.0.ordered_qty']);

        expect($form->errors()->get('form.items.0.ordered_qty'))
            ->toContain('The ordered quantity (line 1) must be at least the quantity received now.');
    });

    it('requires an ordered quantity of at least 1', function () {
        pdPartialForm([pdRow($this->itemA, '', 0)])->call('save')->assertHasErrors(['form.items.0.ordered_qty']);
        pdPartialForm([pdRow($this->itemA, 0, 0)])->call('save')->assertHasErrors(['form.items.0.ordered_qty']);
    });

    it('rejects the same item on two lines', function () {
        $form = pdPartialForm([pdRow($this->itemA, 10, 5), pdRow($this->itemA, 10, 5)])->call('save')
            ->assertHasErrors(['form.items.1.inventory_item_id']);

        expect($form->errors()->get('form.items.1.inventory_item_id'))->toContain('This item is already on another line.');
    });

    it('rejects a partial delivery where nothing is received now', function () {
        $form = pdPartialForm([pdRow($this->itemA, 10, 0, ['storage_location_id' => '', 'unit_cost' => ''])])->call('save')
            ->assertHasErrors(['form.items']);

        expect($form->errors()->get('form.items'))->toContain('Receive at least one item (quantity above 0).')
            ->and(StockReceipts::count())->toBe(0);
    });

    it('still requires a quantity of at least 1 on a complete delivery', function () {
        pdPartialForm([pdRow($this->itemA, '', 0)])
            ->set('form.status', ReceiptStatus::Complete->value)
            ->call('save')
            ->assertHasErrors(['form.items.0.quantity']);
    });

    it('creates no ordered lines for a complete delivery', function () {
        pdPartialForm([pdRow($this->itemA, '', 10)])
            ->set('form.status', ReceiptStatus::Complete->value)
            ->call('save')
            ->assertHasNoErrors();

        $receipt = StockReceipts::sole();
        expect($receipt->status)->toBe(ReceiptStatus::Complete)
            ->and($receipt->lines)->toHaveCount(0);
    });
});

describe('follow-up deliveries', function () {
    it('asks the modal to open from the receiving list', function () {
        $root = pdRoot();

        Livewire::test(Receiving::class)
            ->call('receiveRemaining', $root->id)
            ->assertDispatched('receive-follow-up', receiptId: $root->id);
    });

    it('prefills the form from what is still outstanding and opens the modal', function () {
        $root = pdRoot();
        $root->update(['is_donation' => true]);

        $form = Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $root->id)
            ->assertSet('followUpOf', $root->id)
            ->assertSet('form.parent_receipt_id', $root->id)
            ->assertSet('form.supplier_id', $root->supplier_id)
            ->assertSet('form.is_donation', true)
            ->assertDispatched('modal-show', name: 'create-receiving');

        $items = collect($form->get('form.items'))->keyBy('inventory_item_id');
        expect($items)->toHaveCount(2)
            ->and($items[$this->itemA->id]['quantity'])->toBe(60)
            ->and($items[$this->itemA->id]['storage_location_id'])->toBe($this->location->id)
            ->and($items[$this->itemB->id]['quantity'])->toBe(50);

        $lines = $form->instance()->followUpLines->keyBy(fn ($l) => $l['item']->id);
        expect($lines[$this->itemA->id])->toMatchArray(['ordered' => 100, 'received' => 40, 'outstanding' => 60])
            ->and($form->instance()->followUpRoot->id)->toBe($root->id);

        $files = $form->instance()->inheritedAttachments;
        expect($files)->toHaveCount(1)
            ->and($files[0]['pdf'])->toBeTrue();
    });

    it('can be saved without attachments and links to the root', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.0.quantity', 30)
            ->set('form.items.1.quantity', 0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('stock-received');

        $child = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();

        expect($child->isFollowUp())->toBeTrue()
            ->and($child->root()->id)->toBe($root->id)
            ->and($child->supplier_id)->toBe($root->supplier_id)
            ->and($child->attachments)->toHaveCount(0)
            ->and($child->lines)->toHaveCount(0)
            ->and($child->status)->toBe(ReceiptStatus::Partial)
            ->and($child->inventoryBatches)->toHaveCount(1);

        $ledger = InventoryTransactions::query()->where('reference_id', $child->id)->get();
        expect($ledger)->toHaveCount(1)
            ->and($ledger->first()->quantity)->toBe(30)
            ->and($ledger->first()->balance_after)->toBe(70);

        $root->refresh();
        expect($root->status)->toBe(ReceiptStatus::Partial)
            ->and($root->completed_at)->toBeNull()
            ->and(app(DeliveryProgress::class)->outstanding($root))->toBe(80);
    });

    it('accepts optional attachments but rejects a bad file type', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.quantity', 0)
            ->set('attachments', [UploadedFile::fake()->create('a.txt', 5, 'text/plain')])
            ->call('save')
            ->assertHasErrors(['attachments.0']);

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.quantity', 0)
            ->set('attachments', [pdPdf('followup.pdf')])
            ->call('save')
            ->assertHasNoErrors();

        expect(StockReceipts::query()->where('parent_receipt_id', $root->id)->sole()->attachments)->toHaveCount(1);
    });

    it('flips the root to fully delivered when nothing is outstanding', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.unit_cost', 5)
            ->set('form.items.1.storage_location_id', $this->location->id)
            ->call('save')
            ->assertHasNoErrors();

        $child = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();
        $root->refresh();

        expect($child->status)->toBe(ReceiptStatus::Complete)
            ->and($child->completed_at)->not->toBeNull()
            ->and($root->status)->toBe(ReceiptStatus::Complete)
            ->and($root->completed_at)->not->toBeNull()
            ->and(app(DeliveryProgress::class)->isComplete($root))->toBeTrue()
            ->and(app(DeliveryProgress::class)->outstanding($root))->toBe(0)
            ->and(StockReceipts::query()->openPartial()->count())->toBe(0);
    });

    it('refuses a further follow-up once the delivery is complete', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.unit_cost', 5)
            ->set('form.items.1.storage_location_id', $this->location->id)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $root->id)
            ->assertNotDispatched('modal-show')
            ->assertSet('followUpOf', null)
            ->assertSet('form.parent_receipt_id', null);

        $batches = InventoryBatches::count();

        Livewire::test(ReceivingCreateForm::class)
            ->set('form.supplier_id', $this->supplier->id)
            ->set('form.reference_number', pdRef())
            ->set('form.parent_receipt_id', $root->id)
            ->set('form.items', [pdRow($this->itemA, '', 1)])
            ->call('save')
            ->assertHasErrors(['form.parent_receipt_id']);

        expect(InventoryBatches::count())->toBe($batches);
    });

    it('refuses a follow-up on a closed delivery', function () {
        $root = pdRoot();
        app(DeliveryProgress::class)->close($root, 'Supplier cancelled the balance');

        Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $root->id)
            ->assertNotDispatched('modal-show');
    });

    it('rejects more than what is still outstanding', function () {
        $root = pdRoot();

        $form = pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.0.quantity', 61)
            ->set('form.items.1.quantity', 0)
            ->call('save')
            ->assertHasErrors(['form.items.0.quantity']);

        expect($form->errors()->get('form.items.0.quantity'))->toContain('Only 60 still outstanding for this item.')
            ->and(StockReceipts::query()->where('parent_receipt_id', $root->id)->count())->toBe(0);
    });

    it('rejects an item that was not part of the original delivery', function () {
        $root = pdRoot();

        $form = pdFollowUp($root)
            ->set('form.items.0.inventory_item_id', $this->itemC->id)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.quantity', 0)
            ->call('save')
            ->assertHasErrors(['form.items.0.inventory_item_id']);

        expect($form->errors()->get('form.items.0.inventory_item_id'))->toContain('This item is not part of the original delivery.');
    });

    it('rejects a follow-up that receives nothing', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.quantity', 0)
            ->set('form.items.1.quantity', 0)
            ->call('save')
            ->assertHasErrors(['form.items']);
    });

    it('always books a follow-up against the root supplier', function () {
        $root = pdRoot();
        $other = Suppliers::factory()->create();

        pdFollowUp($root)
            ->set('form.supplier_id', $other->id)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.quantity', 0)
            ->call('save')
            ->assertHasNoErrors();

        expect(StockReceipts::query()->where('parent_receipt_id', $root->id)->sole()->supplier_id)->toBe($root->supplier_id);
    });

    it('keeps the receipt ledger equal to batch received quantities across the whole chain', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.0.quantity', 25)
            ->set('form.items.1.quantity', 0)
            ->call('save')->assertHasNoErrors();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.0.quantity', 35)
            ->set('form.items.1.unit_cost', 5)
            ->set('form.items.1.storage_location_id', $this->location->id)
            ->call('save')->assertHasNoErrors();

        $ids = StockReceipts::query()->where('id', $root->id)->orWhere('parent_receipt_id', $root->id)->pluck('id');

        $ledger = (int) InventoryTransactions::query()
            ->where('type', InventoryTransactionType::Receipt)
            ->whereIn('reference_id', $ids)
            ->sum('quantity');
        $received = (int) InventoryBatches::query()->whereIn('stock_receipt_id', $ids)->sum('received_qty');

        expect($ledger)->toBe($received)->toBe(150)
            ->and($ids)->toHaveCount(3);

        // Every row's balance_after matches the item's on-hand total at that point.
        $lastForB = InventoryTransactions::query()->where('inventory_item_id', $this->itemB->id)->latest('id')->first();
        expect($lastForB->balance_after)->toBe((int) InventoryBatches::where('inventory_item_id', $this->itemB->id)->sum('remaining_qty'));
    });
});

describe('closing a delivery early', function () {
    it('opens the close modal for an open partial delivery', function () {
        $root = pdRoot();

        Livewire::test(Receiving::class)
            ->call('confirmClose', $root->id)
            ->assertSet('closingId', $root->id)
            ->assertDispatched('modal-show', name: 'close-delivery');
    });

    it('requires a reason', function () {
        $root = pdRoot();

        Livewire::test(Receiving::class)
            ->call('confirmClose', $root->id)
            ->call('closeDelivery')
            ->assertHasErrors(['closeReason' => 'required'])
            ->set('closeReason', str_repeat('x', 501))
            ->call('closeDelivery')
            ->assertHasErrors(['closeReason' => 'max']);

        expect($root->fresh()->status)->toBe(ReceiptStatus::Partial);
    });

    it('closes with a reason without moving any stock', function () {
        $root = pdRoot();
        $batches = InventoryBatches::count();
        $ledger = InventoryTransactions::count();
        $onHand = (int) InventoryBatches::sum('remaining_qty');

        Livewire::test(Receiving::class)
            ->call('confirmClose', $root->id)
            ->set('closeReason', 'Supplier cannot fulfil the balance')
            ->call('closeDelivery')
            ->assertHasNoErrors()
            ->assertSet('closingId', null)
            ->assertSet('closeReason', '');

        $root->refresh();

        expect($root->status)->toBe(ReceiptStatus::Closed)
            ->and($root->status->label())->toBe('Closed – Short')
            ->and($root->completed_at)->not->toBeNull()
            ->and($root->closed_reason)->toBe('Supplier cannot fulfil the balance')
            ->and(InventoryBatches::count())->toBe($batches)
            ->and(InventoryTransactions::count())->toBe($ledger)
            ->and((int) InventoryBatches::sum('remaining_qty'))->toBe($onHand)
            ->and(StockReceipts::query()->openPartial()->count())->toBe(0);
    });

    it('cannot close a delivery that is not an open partial root', function () {
        $complete = StockReceipts::factory()->complete()->create(['supplier_id' => $this->supplier->id]);
        $closed = pdRoot();
        app(DeliveryProgress::class)->close($closed, 'Done');

        Livewire::test(Receiving::class)
            ->call('confirmClose', $complete->id)
            ->assertSet('closingId', null)
            ->assertNotDispatched('modal-show')
            ->call('confirmClose', $closed->id)
            ->assertSet('closingId', null)
            ->assertNotDispatched('modal-show');

        foreach ([$complete, $closed] as $receipt) {
            try {
                app(DeliveryProgress::class)->close($receipt->fresh(), 'again');
                $this->fail('Expected a ValidationException.');
            } catch (ValidationException $e) {
                expect($e->errors())->toHaveKey('closing');
            }
        }
    });

    it('cannot close a follow-up delivery', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.quantity', 0)
            ->call('save')->assertHasNoErrors();

        $child = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();

        expect(fn () => app(DeliveryProgress::class)->close($child, 'nope'))->toThrow(ValidationException::class);
    });
});

describe('viewing a delivery', function () {
    it('lists ordered, received and outstanding per item for any receipt in the chain', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.0.quantity', 10)
            ->set('form.items.1.quantity', 0)
            ->call('save')->assertHasNoErrors();

        $child = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();

        foreach ([$root, $child] as $receipt) {
            $page = Livewire::test(Receiving::class)
                ->call('view', $receipt->id)
                ->assertSet('viewingId', $receipt->id)
                ->assertDispatched('modal-show', name: 'view-receipt');

            $lines = collect($page->instance()->viewingTimeline['lines'])->keyBy(fn ($l) => $l['item']->id);
            $received = fn (array $l) => $l['earlier'] + $l['now'] + $l['later'];

            expect($lines[$this->itemA->id])->toMatchArray(['ordered' => 100, 'outstanding' => 50])
                ->and($received($lines[$this->itemA->id]))->toBe(50)
                ->and($lines[$this->itemB->id])->toMatchArray(['ordered' => 50, 'outstanding' => 50])
                ->and($received($lines[$this->itemB->id]))->toBe(0)
                ->and($page->instance()->viewingSummary)->toMatchArray(['ordered' => 150, 'received' => 50, 'outstanding' => 100]);
        }
    });

    it('groups the root and follow-up files, marking the viewed receipt as current', function () {
        $root = pdRoot();

        pdFollowUp($root)
            ->set('form.items.0.unit_cost', 5)
            ->set('form.items.1.quantity', 0)
            ->set('attachments', [pdPdf('followup.pdf'), UploadedFile::fake()->image('photo.jpg')])
            ->call('save')->assertHasNoErrors();

        $child = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();

        $groups = Livewire::test(Receiving::class)->call('view', $child->id)->instance()->viewingAttachments;

        expect($groups)->toHaveCount(2)
            ->and($groups[0]['receipt_id'])->toBe($root->id)
            ->and($groups[0]['reference_number'])->toBe($root->reference_number)
            ->and($groups[0]['is_current'])->toBeFalse()
            ->and($groups[0]['files'])->toHaveCount(1)
            ->and($groups[1]['receipt_id'])->toBe($child->id)
            ->and($groups[1]['is_current'])->toBeTrue()
            ->and($groups[1]['files'])->toHaveCount(2);

        $fromRoot = Livewire::test(Receiving::class)->call('view', $root->id)->instance()->viewingAttachments;
        expect($fromRoot[0]['is_current'])->toBeTrue()
            ->and($fromRoot[1]['is_current'])->toBeFalse();
    });

    it('counts outstanding units for open partial roots in the list', function () {
        $root = pdRoot();

        expect(Livewire::test(Receiving::class)->instance()->progressByReceipt[$root->id]['outstanding'])->toBe(110);
    });
});
