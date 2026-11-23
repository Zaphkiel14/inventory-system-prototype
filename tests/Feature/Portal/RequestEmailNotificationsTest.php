<?php

use App\Enums\RequisitionStatus;
use App\Livewire\Portal\RequestForm;
use App\Livewire\Portal\TrackRequest;
use App\Models\InventoryCategories;
use App\Models\InventoryItems;
use App\Models\Requisitions;
use App\Notifications\RequisitionReceived;
use App\Notifications\RequisitionStatusUpdated;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.administrative_areas.url' => 'https://areas.test/api/administrative-areas']);
    Http::fake([
        'areas.test/*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 6, 'name' => 'Finance Office', 'alias' => 'Finance', 'abbreviation' => 'FO', 'type' => 'office', 'parent_id' => null],
                ['id' => 48, 'name' => 'Accounting Section', 'alias' => 'Accounting Section', 'abbreviation' => 'AS', 'type' => 'section', 'parent_id' => 6],
            ],
        ]),
    ]);

    Notification::fake();
    RateLimiter::clear('public-request:127.0.0.1');
    RateLimiter::clear('track-request:127.0.0.1');

    InventoryCategories::factory()->create();
    $this->paper = InventoryItems::factory()->create(['name' => 'A4 Copy Paper', 'is_active' => true]);
});

function submitWithEmail(?string $email)
{
    $component = Livewire::test(RequestForm::class)
        ->set('form.requester_name', 'Juan Dela Cruz')
        ->set('form.supervisor_name', 'Maria Santos')
        ->set('form.administrative_area_id', 48)
        ->set('form.purpose', 'Monthly supplies')
        ->set('form.items.0.inventory_item_id', test()->paper->id)
        ->set('form.items.0.requested_qty', 5);

    if ($email !== null) {
        $component->set('form.requester_email', $email);
    }

    return $component->call('review');
}

test('a request without an email is created and nothing is sent', function () {
    submitWithEmail(null)->assertHasNoErrors()->call('submit')->assertSet('step', 'done');

    expect(Requisitions::sole()->requester_email)->toBeNull();
    Notification::assertNothingSent();
});

test('a whitespace-only email is stored as null', function () {
    submitWithEmail('   ')->assertHasNoErrors()->call('submit');

    expect(Requisitions::sole()->requester_email)->toBeNull();
    Notification::assertNothingSent();
});

test('an invalid email is rejected and nothing is created', function () {
    submitWithEmail('not-an-email')->assertHasErrors('form.requester_email');

    expect(Requisitions::count())->toBe(0);
    Notification::assertNothingSent();
});

test('a valid mixed-case email is stored lowercase and a confirmation is sent once', function () {
    submitWithEmail('Juan.DelaCruz@Gmail.com')->assertHasNoErrors()->call('submit')->assertSet('step', 'done');

    $requisition = Requisitions::sole();
    expect($requisition->requester_email)->toBe('juan.delacruz@gmail.com');

    Notification::assertSentOnDemandTimes(RequisitionReceived::class, 1);
    Notification::assertSentOnDemand(
        RequisitionReceived::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'juan.delacruz@gmail.com'
            && $notification->requisition->is($requisition)
            && $channels === ['mail'],
    );
});

test('creating a request does not trigger a status update email', function () {
    submitWithEmail('juan@gmail.com')->call('submit');

    Notification::assertSentOnDemandTimes(RequisitionStatusUpdated::class, 0);
});

test('the public tracker never shows the requester email', function () {
    submitWithEmail('secret.person@gmail.com')->call('submit');
    $requisition = Requisitions::sole();

    Livewire::test(TrackRequest::class)
        ->set('code', $requisition->tracking_code)
        ->call('lookup')
        ->assertSet('requisitionId', $requisition->id)
        ->assertSee($requisition->tracking_code)
        ->assertSee(RequisitionStatus::Pending->label())
        ->assertDontSee('secret.person@gmail.com')
        ->assertDontSee('secret.person');
});
