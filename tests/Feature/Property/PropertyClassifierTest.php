<?php

use App\Enums\PropertyClass;
use App\Enums\PropertyDocumentType;
use App\Models\InventoryItems;
use App\Services\Property\PropertyClassifier;
use App\Support\PropertySettings;

it('classifies a property unit by its cost against the default thresholds', function (string|float $unitCost, PropertyClass $expected) {
    $item = InventoryItems::factory()->property()->make();

    expect(app(PropertyClassifier::class)->classify($item, $unitCost))->toBe($expected);
})->with([
    'well below the SPLV ceiling' => ['1196.11', PropertyClass::Splv],
    'exactly the SPLV ceiling' => ['5000.00', PropertyClass::Splv],
    'one centavo above the SPLV ceiling' => ['5000.01', PropertyClass::Sphv],
    'one centavo below the PPE floor' => ['49999.99', PropertyClass::Sphv],
    'exactly the PPE floor' => ['50000.00', PropertyClass::Ppe],
    'well above the PPE floor' => ['78000.00', PropertyClass::Ppe],
    'a float one centavo above the SPLV ceiling' => [5000.01, PropertyClass::Sphv],
]);

it('classifies an item that is not property as consumable whatever its cost', function (string $unitCost) {
    $item = InventoryItems::factory()->make(['is_property' => false]);

    expect(app(PropertyClassifier::class)->classify($item, $unitCost))->toBe(PropertyClass::Consumable);
})->with([
    'low cost' => ['12.50'],
    'SPHV range cost' => ['6000.00'],
    'PPE range cost' => ['78000.00'],
]);

it('uses the thresholds saved in settings', function (string $unitCost, PropertyClass $expected) {
    PropertySettings::setSplvThreshold('1000');
    PropertySettings::setPpeThreshold('30000');
    $item = InventoryItems::factory()->property()->make();

    expect(app(PropertyClassifier::class)->classify($item, $unitCost))->toBe($expected);
})->with([
    'exactly the new SPLV ceiling' => ['1000.00', PropertyClass::Splv],
    'above the new SPLV ceiling but below the old one' => ['1000.01', PropertyClass::Sphv],
    'below the new PPE floor' => ['29999.99', PropertyClass::Sphv],
    'exactly the new PPE floor but below the old one' => ['30000.00', PropertyClass::Ppe],
]);

it('suggests the document each class belongs on', function (PropertyClass $class, ?PropertyDocumentType $expected) {
    expect(app(PropertyClassifier::class)->suggestedForm($class))->toBe($expected);
})->with([
    'PPE goes on a PAR' => [PropertyClass::Ppe, PropertyDocumentType::Par],
    'SPHV goes on an ICS' => [PropertyClass::Sphv, PropertyDocumentType::Ics],
    'SPLV goes on an ICS' => [PropertyClass::Splv, PropertyDocumentType::Ics],
    'consumables go on neither' => [PropertyClass::Consumable, null],
]);
