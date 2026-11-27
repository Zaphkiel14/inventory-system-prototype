<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Fake the administrative-areas API with a small hierarchy and block any
 * other outbound request:
 *  1 Medical Center Chief (root) → 10 Nursing Service Division → 11 Emergency Unit
 *  6 Finance Office (root) → 48 Accounting Section
 */
function fakeAreaHierarchy(): void
{
    config(['services.administrative_areas.url' => 'https://areas.test/api/administrative-areas']);

    Http::preventStrayRequests();
    Http::fake([
        'areas.test/*' => Http::response([
            'status' => 200,
            'data' => [
                ['id' => 1, 'name' => 'Office of the Medical Center Chief', 'abbreviation' => 'OMCC', 'type' => 'office', 'parent_id' => null],
                ['id' => 10, 'name' => 'Nursing Service Division', 'abbreviation' => 'NSD', 'type' => 'division', 'parent_id' => 1],
                ['id' => 11, 'name' => 'Emergency Unit', 'abbreviation' => 'EU', 'type' => 'unit', 'parent_id' => 10],
                ['id' => 6, 'name' => 'Finance Office', 'abbreviation' => 'FO', 'type' => 'office', 'parent_id' => null],
                ['id' => 48, 'name' => 'Accounting Section', 'abbreviation' => 'AS', 'type' => 'section', 'parent_id' => 6],
            ],
        ]),
    ]);
}

/**
 * Fill the RIS header an approval requires with valid values: a RIS No.
 * that matches the default pattern and is unique per call, a fund cluster
 * and the approver's designation (the approver's name is pre-filled).
 */
function fillRisHeader(Testable $page): Testable
{
    static $sequence = 0;
    $sequence++;

    return $page
        ->set('form.ris_number', sprintf('2611%04d', $sequence))
        ->set('form.fund_cluster', '01 - Regular Agency Fund')
        ->set('form.approved_by_designation', 'Chief Administrative Officer');
}
