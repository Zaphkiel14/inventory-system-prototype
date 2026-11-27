<?php

use App\Enums\PropertyClass;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyDocuments;
use App\Models\User;

it('streams an ICS or PAR as an inline PDF named after its number', function (Closure $document, Closure $filename) {
    $this->actingAs(User::factory()->create());
    $document = $document();
    PropertyDocumentLines::factory()->for($document, 'document')->create();

    $this->get(route('property-management.documents.pdf', $document))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename='.$filename($document));
})->with([
    'a finalized ICS' => [
        fn () => PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create(['document_number' => 'SPLV-2026-11-001']),
        fn () => 'ICS-splv-2026-11-001.pdf',
    ],
    'a finalized PAR' => [
        fn () => PropertyDocuments::factory()->par()->finalized()->create(['document_number' => '2026-11-003']),
        fn () => 'PAR-2026-11-003.pdf',
    ],
    'a draft without a number' => [
        fn () => PropertyDocuments::factory()->par()->create(),
        fn (PropertyDocuments $document) => "DRAFT-{$document->id}.pdf",
    ],
]);

it('redirects a guest to the login page instead of serving the document', function () {
    $document = PropertyDocuments::factory()->ics(PropertyClass::Splv)->finalized()->create();

    $this->get(route('property-management.documents.pdf', $document))->assertRedirect(route('login'));
});
