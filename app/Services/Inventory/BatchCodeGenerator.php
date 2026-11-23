<?php

namespace App\Services\Inventory;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Generates batch / lot / request numbers from the formats in config/inventory.php.
 */
class BatchCodeGenerator
{
    /**
     * Numbering key (also the column name) => table that stores the codes.
     */
    protected const TABLES = [
        'batch_number' => 'inventory_batches',
        'lot_number' => 'inventory_batches',
        'request_number' => 'requisitions',
        'issuance_number' => 'stock_issuances',
    ];

    public function batchNumber(): string
    {
        return $this->generate('batch_number');
    }

    public function lotNumber(): string
    {
        return $this->generate('lot_number');
    }

    public function requestNumber(): string
    {
        return $this->generate('request_number');
    }

    public function issuanceNumber(): string
    {
        return $this->generate('issuance_number');
    }

    /**
     * Random public tracking code for a requisition (e.g. TRK-7F3K9Q2M).
     * Unlike the sequential numbers it can't be guessed, so it is safe to
     * hand to requesters for looking up their request status.
     */
    public function trackingCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I

        do {
            $code = 'TRK-';

            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (DB::table('requisitions')->where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Generate the next code for the given numbering key.
     */
    public function generate(string $column, ?Carbon $date = null): string
    {
        $format = config("inventory.numbering.{$column}");

        if (! is_string($format) || $format === '') {
            throw new InvalidArgumentException("No numbering format configured for [{$column}].");
        }

        $date ??= now();

        $rendered = preg_replace_callback(
            '/\{date:([^}]+)\}/',
            fn (array $m) => $date->format($m[1]),
            $format,
        );

        if (! preg_match('/\{seq:(\d+)\}/', $rendered, $match, PREG_OFFSET_CAPTURE)) {
            // No sequence token: the format is used as-is.
            return $rendered;
        }

        $padding = (int) $match[1][0];
        $prefix = substr($rendered, 0, $match[0][1]);
        $suffix = substr($rendered, $match[0][1] + strlen($match[0][0]));

        $next = $this->lastSequence($column, $prefix, $suffix) + 1;

        return $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT).$suffix;
    }

    /**
     * Highest sequence already used for this prefix/suffix (0 if none).
     */
    protected function lastSequence(string $column, string $prefix, string $suffix): int
    {
        // LIKE narrows the rows; the regex below does the exact match, so any
        // stray LIKE wildcards in a custom prefix can only over-match, never miss.
        $pattern = '/^'.preg_quote($prefix, '/').'(\d+)'.preg_quote($suffix, '/').'$/';

        return DB::table(self::TABLES[$column] ?? 'inventory_batches')
            ->where($column, 'like', $prefix.'%'.$suffix)
            ->pluck($column)
            ->map(fn (string $code) => preg_match($pattern, $code, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;
    }
}
