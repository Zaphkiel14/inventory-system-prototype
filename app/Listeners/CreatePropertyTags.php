<?php

namespace App\Listeners;

use App\Enums\PropertyDocumentType;
use App\Events\PropertyDocumentFinalized;
use App\Models\PropertyDocumentLines;
use App\Models\PropertyTags;
use App\Support\PropertySettings;

/**
 * Queues a Pending property tag for each line of a finalized document that
 * gets one: every PAR line, and ICS lines as the ICS tagging setting allows.
 * Idempotent: the unique line FK means a line never gets a second tag.
 */
class CreatePropertyTags
{
    public function handle(PropertyDocumentFinalized $event): void
    {
        $document = $event->document;

        $tagged = $document->type === PropertyDocumentType::Par
            || ($document->ics_class !== null && PropertySettings::icsTagging()->tags($document->ics_class));

        if (! $tagged) {
            return;
        }

        $document->lines()->get()->each(
            fn (PropertyDocumentLines $line) => PropertyTags::query()->firstOrCreate(['property_document_line_id' => $line->id]),
        );
    }
}
