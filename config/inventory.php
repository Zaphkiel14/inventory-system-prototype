<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Batch & Lot Number Formats
    |--------------------------------------------------------------------------
    |
    | Formats used to auto-generate batch and lot numbers when they are left
    | blank while receiving stock. Available tokens:
    |
    |   {date:FORMAT}  Current date using any PHP date() format, e.g. {date:Ymd}
    |   {seq:N}        Running sequence, zero-padded to N digits, e.g. {seq:5}
    |
    | The sequence restarts whenever the text before {seq} changes, so with
    | a {date:Ymd} token it resets every day.
    |
    */

    'numbering' => [
        'batch_number' => 'BAT-{date:Ymd}-{seq:5}',
        'lot_number' => 'LOT-{date:Ymd}-{seq:5}',

        // Requisition request numbers (resets monthly).
        'request_number' => 'REQ-{date:Ym}-{seq:4}',

        // Stock issuance (release) numbers (resets monthly).
        'issuance_number' => 'ISS-{date:Ym}-{seq:4}',
    ],

];
