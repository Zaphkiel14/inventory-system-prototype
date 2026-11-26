<?php

namespace App\Services\Inventory;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Generates batch / lot / request numbers from the formats in config/inventory.php.
 *
 * Each instance remembers the last sequence it handed out per column and
 * prefix, so a multi-row store() reads the table once per prefix instead of
 * once per row. Resolve a fresh instance per write (app(...) inside store(),
 * inside its DB::transaction). Never bind it as a singleton: a long-running
 * queue worker would keep stale sequences and hand out duplicates.
 */
class BatchCodeGenerator
{
    /**
     * Last sequence handed out, keyed by "column|prefix|suffix".
     *
     * @var array<string, int>
     */
    protected array $sequences = [];

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
    public function generate(string $column, ?CarbonInterface $date = null): string
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

        // The key includes the prefix, so a new date (new prefix) starts from
        // the table again and the sequence resets as before.
        $key = "{$column}|{$prefix}|{$suffix}";
        $next = ($this->sequences[$key] ??= $this->lastSequence($column, $prefix, $suffix)) + 1;
        $this->sequences[$key] = $next;

        return $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT).$suffix;
    }

    /**
     * Record a code typed in by hand, so a later generate() on this instance
     * doesn't repeat its sequence number. Only matters for prefixes this
     * instance has already generated (others are read from the table).
     */
    public function claim(string $column, string $code): void
    {
        foreach ($this->sequences as $key => $last) {
            [$keyColumn, $prefix, $suffix] = explode('|', $key, 3);

            if ($keyColumn === $column && preg_match($this->sequencePattern($prefix, $suffix), $code, $m)) {
                $this->sequences[$key] = max($last, (int) $m[1]);
            }
        }
    }

    /**
     * Forget the remembered sequences (the next generate() re-reads the table).
     */
    public function forgetSequences(): void
    {
        $this->sequences = [];
    }

    /**
     * Highest sequence already used for this prefix/suffix (0 if none).
     */
    protected function lastSequence(string $column, string $prefix, string $suffix): int
    {
        // The range bounds select only codes starting with the prefix and can
        // use the column index (SQLite's LIKE can't); LIKE narrows on the
        // suffix and the regex does the exact match, so stray wildcards in a
        // custom prefix can only over-match, never miss.
        $pattern = $this->sequencePattern($prefix, $suffix);

        return DB::table(self::TABLES[$column] ?? 'inventory_batches')
            ->where($column, '>=', $prefix)
            ->where($column, '<', $prefix."\u{10FFFF}")
            ->where($column, 'like', $prefix.'%'.$suffix)
            ->pluck($column)
            ->map(fn (string $code) => preg_match($pattern, $code, $m) ? (int) $m[1] : 0)
            ->max() ?? 0;
    }

    protected function sequencePattern(string $prefix, string $suffix): string
    {
        return '/^'.preg_quote($prefix, '/').'(\d+)'.preg_quote($suffix, '/').'$/';
    }
}
