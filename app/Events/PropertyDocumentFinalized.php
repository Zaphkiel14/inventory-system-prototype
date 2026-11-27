<?php

namespace App\Events;

use App\Models\PropertyDocuments;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An ICS or PAR was finalized (status Active, numbers locked in). Dispatched
 * after the transaction commits; Phase 4 creates the property tags here.
 */
class PropertyDocumentFinalized implements ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PropertyDocuments $document) {}
}
