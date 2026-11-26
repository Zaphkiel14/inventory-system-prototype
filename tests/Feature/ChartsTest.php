<?php

use App\Models\User;

it('returns 404 for the chart demo page outside the local environment', function () {
    expect(app()->environment('local'))->toBeFalse();

    $this->actingAs(User::factory()->create())
        ->get('/charts')
        ->assertNotFound();
});
