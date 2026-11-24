<?php

use App\Services\AdministrativeAreas\AdministrativeAreaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    config(['services.administrative_areas.url' => 'https://areas.test/api/administrative-areas']);

    Http::fake([
        'areas.test/*' => Http::response([
            'data' => [
                ['id' => 48, 'name' => 'Accounting Section', 'abbreviation' => 'AS', 'type' => 'section', 'parent_id' => 6],
                ['id' => 6, 'name' => 'Finance Office', 'abbreviation' => 'FO', 'type' => 'office', 'parent_id' => null],
                ['id' => 70, 'name' => 'Billing Unit', 'abbreviation' => null, 'type' => 'unit', 'parent_id' => 48],
                ['id' => 3, 'name' => 'Allied Office', 'abbreviation' => 'ALLIED', 'type' => 'office', 'parent_id' => null],
                ['id' => 65, 'name' => 'Admitting Unit', 'abbreviation' => 'AU', 'type' => 'unit', 'parent_id' => 3],
                ['id' => 99, 'name' => 'Lost Unit', 'abbreviation' => null, 'type' => 'unit', 'parent_id' => 12345],
            ],
        ]),
    ]);
});

test('areas are grouped under their top-level parent with descendants indented', function () {
    $groups = app(AdministrativeAreaService::class)->groupedOptions();

    expect($groups->pluck('label')->all())->toBe(['Allied Office (ALLIED)', 'Finance Office (FO)', 'Other'])
        ->and($groups[0]['options']->all())->toBe([3 => 'Allied Office (ALLIED)', 65 => '— Admitting Unit (AU)'])
        ->and($groups[1]['options']->all())->toBe([
            6 => 'Finance Office (FO)',
            48 => '— Accounting Section (AS)',
            70 => '— — Billing Unit',
        ])
        ->and($groups[2]['options']->all())->toBe([99 => 'Lost Unit']);
});

test('the area list is cached for six hours', function () {
    $service = app(AdministrativeAreaService::class);

    $service->all();

    $this->travel(5)->hours();
    $service->all();
    Http::assertSentCount(1);

    $this->travel(61)->minutes();
    $service->all();
    Http::assertSentCount(2);
});

test('an area label names only the area itself, not its parent', function () {
    $service = app(AdministrativeAreaService::class);

    expect($service->label($service->find(48)))->toBe('Accounting Section (AS) · Section')
        ->and($service->label($service->find(70)))->toBe('Billing Unit · Unit')
        ->and($service->options()->get(48))->not->toContain('Finance Office');
});

test('areas that share a name are told apart by their parent', function () {
    // Own data source: the beforeEach fake (areas.test) would match first otherwise.
    config(['services.administrative_areas.url' => 'https://dupes.test/api/administrative-areas']);
    Http::fake([
        'dupes.test/*' => Http::response([
            'data' => [
                ['id' => 1, 'name' => 'Medical Service Division', 'abbreviation' => 'MSD', 'type' => 'division', 'parent_id' => null],
                ['id' => 2, 'name' => 'Nursing Service Division', 'abbreviation' => 'NSD', 'type' => 'division', 'parent_id' => null],
                ['id' => 26, 'name' => 'Emergency Unit', 'abbreviation' => 'EU', 'type' => 'unit', 'parent_id' => 1],
                ['id' => 82, 'name' => 'Emergency Unit', 'abbreviation' => 'EU', 'type' => 'unit', 'parent_id' => 2],
                ['id' => 40, 'name' => 'Cardiology Unit', 'abbreviation' => 'CU', 'type' => 'unit', 'parent_id' => 1],
            ],
        ]),
    ]);
    Cache::flush();
    $service = app(AdministrativeAreaService::class);

    expect($service->label($service->find(26)))->toBe('Emergency Unit (EU) · Unit · Medical Service Division')
        ->and($service->label($service->find(82)))->toBe('Emergency Unit (EU) · Unit · Nursing Service Division')
        ->and($service->label($service->find(40)))->toBe('Cardiology Unit (CU) · Unit');

    $options = $service->groupedOptions()->reduce(fn (array $carry, array $group) => $carry + $group['options']->all(), []);

    expect($options[26])->toBe('— Emergency Unit (EU) · Medical Service Division')
        ->and($options[82])->toBe('— Emergency Unit (EU) · Nursing Service Division')
        ->and($options[40])->toBe('— Cardiology Unit (CU)');
});
