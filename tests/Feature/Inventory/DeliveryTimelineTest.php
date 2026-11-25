<?php

use App\Enums\ReceiptStatus;
use App\Livewire\Inventory\Receiving;
use App\Livewire\Inventory\ReceivingCreateForm;
use App\Models\InventoryItems;
use App\Models\StockReceipts;
use App\Models\StorageLocations;
use App\Models\Suppliers;
use App\Models\User;
use App\Services\Inventory\DeliveryProgress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $this->supplier = Suppliers::factory()->create();
    $this->location = StorageLocations::factory()->create();
    $this->itemA = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true, 'name' => 'Gauze Pads']);
    $this->itemB = InventoryItems::factory()->create(['requires_expiry' => false, 'is_active' => true, 'name' => 'Saline Bags']);
});

function dtRow(InventoryItems $item, int $ordered, int $qty): array
{
    return [
        'inventory_item_id' => $item->id,
        'storage_location_id' => $qty > 0 ? test()->location->id : '',
        'batch_number' => '',
        'lot_number' => '',
        'ordered_qty' => $ordered,
        'quantity' => $qty,
        'unit_cost' => $qty > 0 ? 5 : '',
        'manufactured_date' => null,
        'expiration_date' => null,
    ];
}

function dtRef(): string
{
    static $n = 0;

    return 'DR-TL-'.++$n;
}

/** Root: A ordered 20 (5 in), B ordered 50 (10 in). */
function dtRoot(array $over = []): StockReceipts
{
    Livewire::test(ReceivingCreateForm::class)
        ->set('form.supplier_id', test()->supplier->id)
        ->set('form.reference_number', dtRef())
        ->set('form.status', ReceiptStatus::Partial->value)
        ->set('form.is_donation', $over['donation'] ?? false)
        ->set('form.items', [dtRow(test()->itemA, 20, 5), dtRow(test()->itemB, 50, 10)])
        ->set('attachments', [UploadedFile::fake()->create('root.pdf', 50, 'application/pdf')])
        ->call('save')
        ->assertHasNoErrors();

    return StockReceipts::query()->whereNull('parent_receipt_id')->latest('id')->firstOrFail();
}

function dtFollowUp(StockReceipts $root, int $a, int $b, bool $withFile = false): StockReceipts
{
    $ref = dtRef();

    $form = Livewire::test(ReceivingCreateForm::class)
        ->call('followUp', $root->id)
        ->set('form.reference_number', $ref)
        ->set('form.items', [dtRow(test()->itemA, 0, $a), dtRow(test()->itemB, 0, $b)]);

    if ($withFile) {
        $form->set('attachments', [UploadedFile::fake()->create('fu.pdf', 50, 'application/pdf')]);
    }

    $form->call('save')->assertHasNoErrors();

    return StockReceipts::query()->where('reference_number', $ref)->firstOrFail();
}

/** @return array<int, array<string, mixed>> lines keyed by item id */
function dtLines(array $timeline): array
{
    return collect($timeline['lines'])->keyBy(fn ($l) => $l['item']->id)->all();
}

describe('timeline service', function () {
    it('splits earlier, now, later and outstanding for each receipt in an open chain', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 20);
        $svc = app(DeliveryProgress::class);
        [$a, $b] = [$this->itemA->id, $this->itemB->id];

        $view = $svc->timeline($root, $root);
        expect(dtLines($view)[$a])->toMatchArray(['ordered' => 20, 'earlier' => 0, 'now' => 5, 'later' => 10, 'outstanding' => 5, 'closed_short' => 0])
            ->and(dtLines($view)[$b])->toMatchArray(['ordered' => 50, 'earlier' => 0, 'now' => 10, 'later' => 20, 'outstanding' => 20]);

        $view = $svc->timeline($root, $f1);
        expect(dtLines($view)[$a])->toMatchArray(['earlier' => 5, 'now' => 10, 'later' => 0, 'outstanding' => 5])
            ->and(dtLines($view)[$b])->toMatchArray(['earlier' => 10, 'now' => 20, 'later' => 0, 'outstanding' => 20]);
    });

    it('shows nothing outstanding once the last follow-up completes the order', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 20);
        $f2 = dtFollowUp($root, 5, 20);
        $svc = app(DeliveryProgress::class);
        [$a, $b] = [$this->itemA->id, $this->itemB->id];

        $view = $svc->timeline($root, $f2);
        expect(dtLines($view)[$a])->toMatchArray(['earlier' => 15, 'now' => 5, 'later' => 0, 'outstanding' => 0])
            ->and(dtLines($view)[$b])->toMatchArray(['earlier' => 30, 'now' => 20, 'later' => 0, 'outstanding' => 0]);

        $view = $svc->timeline($root, $f1);
        expect(dtLines($view)[$a])->toMatchArray(['earlier' => 5, 'now' => 10, 'later' => 5])
            ->and(dtLines($view)[$b])->toMatchArray(['earlier' => 10, 'now' => 20, 'later' => 20]);

        expect($svc->timeline($root->fresh(), $f2)['status'])->toBe(ReceiptStatus::Complete);
    });

    it('adds the totals up for every view', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 20);
        $svc = app(DeliveryProgress::class);

        // Open chain: 45 of 70 received in total, whichever receipt is viewed.
        foreach ([$root, $f1] as $current) {
            $t = $svc->timeline($root, $current);

            expect($t['ordered'])->toBe(70)
                ->and($t['received'])->toBe(45)
                ->and($t['outstanding'])->toBe(25)
                ->and($t['received'] + $t['outstanding'])->toBe($t['ordered'])
                ->and($t['received'])->toBe(
                    collect($t['lines'])->sum(fn ($l) => $l['earlier'] + $l['now'] + $l['later'])
                );
        }

        $f2 = dtFollowUp($root, 5, 20);

        foreach ([$root, $f1, $f2] as $current) {
            $t = $svc->timeline($root, $current);

            expect($t['received'])->toBe(70)->and($t['outstanding'])->toBe(0);
        }
    });

    it('counts later follow-ups for a middle receipt without hiding what is still outstanding', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 5, 10);
        dtFollowUp($root, 2, 5);

        $t = app(DeliveryProgress::class)->timeline($root, $f1);

        expect(dtLines($t)[$this->itemA->id])->toMatchArray(['earlier' => 5, 'now' => 5, 'later' => 2, 'outstanding' => 8])
            ->and(dtLines($t)[$this->itemB->id])->toMatchArray(['earlier' => 10, 'now' => 10, 'later' => 5, 'outstanding' => 25])
            ->and($t['received'])->toBe(37)
            ->and($t['outstanding'])->toBe(33);
    });

    it('shows nothing outstanding when viewing the root of a fully delivered order', function () {
        $root = dtRoot();
        dtFollowUp($root, 15, 40);

        $t = app(DeliveryProgress::class)->timeline($root->fresh(), $root->fresh());

        expect($t['received'])->toBe(70)
            ->and($t['outstanding'])->toBe(0)
            ->and(dtLines($t)[$this->itemA->id])->toMatchArray(['earlier' => 0, 'now' => 5, 'later' => 15, 'outstanding' => 0]);
    });

    it('treats a missing current receipt as everything received so far', function () {
        $root = dtRoot();
        dtFollowUp($root, 10, 20);

        $t = app(DeliveryProgress::class)->timeline($root);

        expect(dtLines($t)[$this->itemA->id])->toMatchArray(['earlier' => 15, 'now' => 0, 'outstanding' => 5])
            ->and($t['received'])->toBe(45)
            ->and($t['outstanding'])->toBe(25);
    });

    it('accepts a follow-up as the root argument', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 20);

        expect(app(DeliveryProgress::class)->timeline($f1, $f1)['ordered'])->toBe(70);
    });

    it('caps a previewed quantity at what is outstanding', function () {
        $root = dtRoot();
        $svc = app(DeliveryProgress::class);
        [$a, $b] = [$this->itemA->id, $this->itemB->id];

        $t = $svc->timeline($root, null, [$a => 999, $b => 7]);

        expect(dtLines($t)[$a])->toMatchArray(['earlier' => 5, 'now' => 15, 'outstanding' => 0])
            ->and(dtLines($t)[$b])->toMatchArray(['earlier' => 10, 'now' => 7, 'outstanding' => 33]);
    });

    it('treats zero, negative and missing previewed quantities as nothing', function () {
        $root = dtRoot();
        $svc = app(DeliveryProgress::class);
        [$a, $b] = [$this->itemA->id, $this->itemB->id];

        $t = $svc->timeline($root, null, [$a => -4, $b => 0]);

        expect(dtLines($t)[$a]['now'])->toBe(0)
            ->and(dtLines($t)[$b]['now'])->toBe(0);

        expect(dtLines($svc->timeline($root))[$a]['now'])->toBe(0);
    });

    it('marks what is outstanding as closed short on a closed root', function () {
        $root = dtRoot();
        app(DeliveryProgress::class)->close($root, 'Supplier discontinued the item');

        $t = app(DeliveryProgress::class)->timeline($root->fresh(), $root->fresh());

        expect($t['status'])->toBe(ReceiptStatus::Closed)
            ->and($t['closed_reason'])->toBe('Supplier discontinued the item')
            ->and(dtLines($t)[$this->itemA->id])->toMatchArray(['outstanding' => 15, 'closed_short' => 15])
            ->and(dtLines($t)[$this->itemB->id])->toMatchArray(['outstanding' => 40, 'closed_short' => 40]);
    });

    it('reports no closed-short quantity while the root is open', function () {
        $root = dtRoot();

        $t = app(DeliveryProgress::class)->timeline($root, $root);

        expect(collect($t['lines'])->sum('closed_short'))->toBe(0)
            ->and($t['closed_reason'])->toBeNull();
    });

    it('lists deliveries root first with items, files and inherited files', function () {
        $root = dtRoot();
        $bare = dtFollowUp($root, 10, 0);
        $withFile = dtFollowUp($root, 0, 20, withFile: true);

        $t = app(DeliveryProgress::class)->timeline($root, $withFile);
        $d = $t['deliveries'];

        expect(collect($d)->pluck('id')->all())->toBe([$root->id, $bare->id, $withFile->id])
            ->and(collect($d)->pluck('is_root')->all())->toBe([true, false, false])
            ->and(collect($d)->pluck('is_current')->all())->toBe([false, false, true])
            ->and(collect($d)->pluck('inherits_files')->all())->toBe([false, true, false])
            ->and($d[0]['reference_number'])->toBe($root->reference_number)
            ->and($d[0]['files'])->toHaveCount(1)
            ->and($d[1]['files'])->toBe([])
            ->and($d[2]['files'])->toHaveCount(1)
            ->and(collect($d[0]['items'])->pluck('qty', 'name')->all())->toBe(['Gauze Pads' => 5, 'Saline Bags' => 10])
            ->and(collect($d[1]['items'])->pluck('qty', 'name')->all())->toBe(['Gauze Pads' => 10])
            ->and(collect($d[2]['items'])->pluck('qty', 'name')->all())->toBe(['Saline Bags' => 20]);
    });

    it('gives every delivery its own status', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 0);
        $svc = app(DeliveryProgress::class);

        $statuses = collect($svc->timeline($root, $f1)['deliveries'])->pluck('status')->all();
        expect($statuses)->toBe([ReceiptStatus::Partial, ReceiptStatus::Partial]);

        $f2 = dtFollowUp($root, 5, 40);
        $statuses = collect($svc->timeline($root->fresh(), $f2)['deliveries'])->pluck('status')->all();
        expect($statuses)->toBe([ReceiptStatus::Complete, ReceiptStatus::Partial, ReceiptStatus::Complete]);

        $closed = dtRoot();
        app(DeliveryProgress::class)->close($closed, 'Supplier stopped');
        expect($svc->timeline($closed->fresh())['deliveries'][0]['status'])->toBe(ReceiptStatus::Closed);
    });

    it('marks no delivery as current when there is no current receipt', function () {
        $root = dtRoot();

        $d = app(DeliveryProgress::class)->timeline($root)['deliveries'];

        expect($d)->toHaveCount(1)
            ->and($d[0]['is_current'])->toBeFalse()
            ->and($d[0]['inherits_files'])->toBeFalse();
    });
});

describe('receiving list and follow-up form state', function () {
    it('has no timeline for a receipt that is not part of a partial delivery', function () {
        $receipt = StockReceipts::factory()->complete()->create(['supplier_id' => $this->supplier->id]);

        $page = Livewire::test(Receiving::class)->call('view', $receipt->id);

        expect($page->instance()->viewingTimeline)->toBeNull();
    });

    it('builds the timeline for a viewed root and follow-up', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 20);

        $page = Livewire::test(Receiving::class)->call('view', $f1->id);
        $timeline = $page->instance()->viewingTimeline;

        expect($timeline['ordered'])->toBe(70)
            ->and(collect($timeline['deliveries'])->firstWhere('is_current', true)['id'])->toBe($f1->id);
    });

    it('reports progress per open partial root on the page', function () {
        $root = dtRoot();
        $other = dtRoot();
        dtFollowUp($root, 10, 20);
        $complete = StockReceipts::factory()->complete()->create(['supplier_id' => $this->supplier->id]);
        $follow = StockReceipts::query()->where('parent_receipt_id', $root->id)->sole();

        $progress = Livewire::test(Receiving::class)->instance()->progressByReceipt;

        expect($progress)->toHaveKeys([$root->id, $other->id])
            ->and($progress)->not->toHaveKey($complete->id)
            ->and($progress)->not->toHaveKey($follow->id)
            ->and($progress[$root->id])->toBe(['received' => 45, 'ordered' => 70, 'outstanding' => 25])
            ->and($progress[$other->id])->toBe(['received' => 15, 'ordered' => 70, 'outstanding' => 55]);
    });

    it('drops closed and completed roots from the progress map', function () {
        $closed = dtRoot();
        app(DeliveryProgress::class)->close($closed, 'No longer needed');
        $done = dtRoot();
        dtFollowUp($done, 15, 40);

        expect(Livewire::test(Receiving::class)->instance()->progressByReceipt)->toBe([]);
    });

    it('computes list progress with one query however many open roots are on the page', function () {
        $queriesFor = function (): int {
            $page = Livewire::test(Receiving::class)->instance();
            unset($page->progressByReceipt);
            $page->receipts;

            DB::flushQueryLog();
            DB::enableQueryLog();
            $page->progressByReceipt;
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        dtFollowUp(dtRoot(), 5, 0);
        $one = $queriesFor();

        dtFollowUp(dtRoot(), 5, 0);
        dtFollowUp(dtRoot(), 0, 10);
        $three = $queriesFor();

        expect($three)->toBe($one);
    });

    it('summarises a plain receipt from its own batches', function () {
        Livewire::test(ReceivingCreateForm::class)
            ->set('form.supplier_id', $this->supplier->id)
            ->set('form.reference_number', 'DR-PLAIN-1')
            ->set('form.items', [dtRow($this->itemA, 12, 12), dtRow($this->itemB, 4, 4)])
            ->set('form.items.0.unit_cost', 2.5)
            ->set('form.items.1.unit_cost', 1.25)
            ->set('attachments', [UploadedFile::fake()->create('plain.pdf', 50, 'application/pdf')])
            ->call('save')
            ->assertHasNoErrors();

        $receipt = StockReceipts::query()->where('reference_number', 'DR-PLAIN-1')->sole();

        expect(Livewire::test(Receiving::class)->call('view', $receipt->id)->instance()->viewingSummary)->toBe([
            'units' => 16,
            'batches' => 2,
            'total_cost' => 35.0,
            'is_chain' => false,
            'ordered' => null,
            'received' => null,
            'outstanding' => null,
            'is_closed' => false,
            'can_receive_remaining' => false,
        ]);
    });

    it('summarises a follow-up with its own batches and the chain totals', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 0);

        $summary = fn () => Livewire::test(Receiving::class)->call('view', $f1->id)->instance()->viewingSummary;

        expect($summary())->toMatchArray([
            'units' => 10,
            'batches' => 1,
            'total_cost' => 50.0,
            'is_chain' => true,
            'ordered' => 70,
            'received' => 25,
            'outstanding' => 45,
            'is_closed' => false,
            'can_receive_remaining' => true,
        ]);

        app(DeliveryProgress::class)->close($root->fresh(), 'Supplier stopped');

        expect($summary())->toMatchArray(['is_closed' => true, 'can_receive_remaining' => false]);
    });

    it('previews what remains after the follow-up as quantities change', function () {
        $root = dtRoot();
        $page = Livewire::test(ReceivingCreateForm::class)->call('followUp', $root->id);

        $a = fn () => collect($page->instance()->followUpRows)->firstWhere('index', 0);

        // Prefilled with everything outstanding.
        expect($a())->toMatchArray(['ordered' => 20, 'received' => 5, 'outstanding' => 15, 'receiving_now' => 15, 'remaining_after' => 0])
            ->and($a()['item']->id)->toBe($this->itemA->id);

        $page->set('form.items.0.quantity', 4);
        expect($a())->toMatchArray(['receiving_now' => 4, 'remaining_after' => 11]);

        $page->set('form.items.0.quantity', 0);
        expect($a())->toMatchArray(['receiving_now' => 0, 'remaining_after' => 15]);

        $page->set('form.items.0.quantity', 999);
        expect($a())->toMatchArray(['receiving_now' => 999, 'remaining_after' => 0]);

        expect($page->instance()->followUpTimeline['deliveries'])->toHaveCount(1);
    });

    it('has no follow-up rows on a new receipt', function () {
        expect(Livewire::test(ReceivingCreateForm::class)->instance()->followUpRows)->toBe([]);
    });

    it('counts every form line of the same item in what remains after the follow-up', function () {
        $root = dtRoot();
        $page = Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $root->id)
            ->set('form.items', [dtRow($this->itemA, 0, 3), dtRow($this->itemA, 0, 4)]);

        // 15 outstanding, 3 + 4 arriving: 8 remain once this delivery is saved.
        $remaining = collect($page->instance()->followUpRows)->pluck('remaining_after');

        expect($remaining->min())->toBe(8);
    });
});

describe('receive remaining from any receipt in an open chain', function () {
    it('knows whether the chain is still open', function () {
        $open = dtRoot();
        $openChild = dtFollowUp($open, 5, 0);

        $done = dtRoot();
        $doneChild = dtFollowUp($done, 15, 40);

        $closed = dtRoot();
        $closedChild = dtFollowUp($closed, 5, 0);
        app(DeliveryProgress::class)->close($closed->fresh(), 'Supplier stopped');

        expect($open->fresh()->isChainOpen())->toBeTrue()
            ->and($openChild->fresh()->isChainOpen())->toBeTrue()
            ->and($done->fresh()->isChainOpen())->toBeFalse()
            ->and($doneChild->fresh()->isChainOpen())->toBeFalse()
            ->and($closed->fresh()->isChainOpen())->toBeFalse()
            ->and($closedChild->fresh()->isChainOpen())->toBeFalse();
    });

    it('offers it on a follow-up row of an open order only', function () {
        $open = dtRoot();
        dtFollowUp($open, 5, 0);

        $html = Livewire::test(Receiving::class)->html();
        expect(substr_count($html, 'Receive remaining'))->toBe(2);

        $done = dtRoot();
        dtFollowUp($done, 15, 40);
        $closed = dtRoot();
        dtFollowUp($closed, 5, 0);
        app(DeliveryProgress::class)->close($closed->fresh(), 'Supplier stopped');

        // Still only the open chain's two rows.
        expect(substr_count(Livewire::test(Receiving::class)->html(), 'Receive remaining'))->toBe(2);
    });

    it('dispatches the root id when receiving remaining from a follow-up', function () {
        $root = dtRoot();
        $child = dtFollowUp($root, 5, 0);

        Livewire::test(Receiving::class)
            ->call('view', $child->id)
            ->call('receiveRemaining', $child->id)
            ->assertDispatched('modal-close', name: 'view-receipt')
            ->assertDispatched('receive-follow-up', receiptId: $root->id);
    });

    it('prefills the form from the root when opened from a follow-up', function () {
        $root = dtRoot();
        $child = dtFollowUp($root, 5, 0);

        $form = Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $child->id)
            ->assertSet('followUpOf', $root->id)
            ->assertSet('form.parent_receipt_id', $root->id)
            ->assertSet('form.supplier_id', $root->supplier_id)
            ->assertDispatched('modal-show', name: 'create-receiving');

        $items = collect($form->get('form.items'))->keyBy('inventory_item_id');

        expect($items[$this->itemA->id]['quantity'])->toBe(10)
            ->and($items[$this->itemB->id]['quantity'])->toBe(40);
    });

    it('is rejected from a follow-up of a complete or closed order', function () {
        $done = dtRoot();
        $doneChild = dtFollowUp($done, 15, 40);
        $closed = dtRoot();
        $closedChild = dtFollowUp($closed, 5, 0);
        app(DeliveryProgress::class)->close($closed->fresh(), 'Supplier stopped');

        foreach ([$doneChild, $closedChild] as $child) {
            Livewire::test(ReceivingCreateForm::class)
                ->call('followUp', $child->id)
                ->assertNotDispatched('modal-show')
                ->assertSet('followUpOf', null)
                ->assertSet('form.parent_receipt_id', null);
        }
    });
});

describe('receipt modal and list UI', function () {
    it('shows the chain timeline on a follow-up', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 0);

        Livewire::test(Receiving::class)
            ->call('view', $f1->id)
            ->assertSee('Delivery progress')
            ->assertSee('25 of 70 units received')
            ->assertSee('this receipt')
            ->assertSee($root->reference_number)
            ->assertSee('(this receipt)')
            ->assertSee('Uses files from '.$root->reference_number);
    });

    it('marks complete and outstanding lines', function () {
        $root = dtRoot();
        dtFollowUp($root, 15, 0);

        Livewire::test(Receiving::class)
            ->call('view', $root->id)
            ->assertSee('· complete')
            ->assertSee('· 40 outstanding')
            ->assertSee('30 of 70 units received');
    });

    it('explains a closed delivery in its modal', function () {
        $root = dtRoot();
        app(DeliveryProgress::class)->close($root, 'Supplier discontinued the item');

        Livewire::test(Receiving::class)
            ->call('view', $root->id)
            ->assertSee('Closed short')
            ->assertSee('· no longer expected')
            ->assertSee('Supplier discontinued the item');
    });

    it('shows each delivery in the chain with its own status', function () {
        $root = dtRoot();
        $f1 = dtFollowUp($root, 10, 0);
        dtFollowUp($root, 5, 40);

        Livewire::test(Receiving::class)
            ->call('view', $f1->id)
            ->assertSee('Fully Delivered')
            ->assertSee('Partial Delivery');
    });

    it('shows the closed status on a closed chain', function () {
        $root = dtRoot();
        app(DeliveryProgress::class)->close($root, 'Supplier stopped');

        Livewire::test(Receiving::class)->call('view', $root->id)->assertSee('Closed – Short');
    });

    it('shows the outstanding table and previous deliveries on the follow-up form', function () {
        $root = dtRoot();

        Livewire::test(ReceivingCreateForm::class)
            ->call('followUp', $root->id)
            ->assertSee('Received so far')
            ->assertSee('Receiving now')
            ->assertSee('After this delivery')
            ->assertSee('Previous deliveries (1)')
            ->assertSee($root->reference_number)
            ->assertSeeHtml('wire:model="form.items.0.batch_number"')
            ->assertSeeHtml('wire:model="form.items.0.lot_number"')
            ->assertDontSee('Optional details');
    });

    it('shows received-of-ordered in the receiving list tooltip', function () {
        dtRoot();

        Livewire::test(Receiving::class)->assertSee('15 of 70 received');
    });
});
