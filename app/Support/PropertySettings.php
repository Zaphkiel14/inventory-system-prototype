<?php

namespace App\Support;

use App\Enums\IcsTagging;
use App\Enums\PaperSize;
use App\Models\Settings;

/**
 * Typed access to the property-document settings stored in the `settings`
 * table. Reads go through the cached InventoryCache::settings() lookup and
 * fall back to the defaults below when a key has never been saved.
 *
 * Thresholds are returned as two-decimal strings ("50000.00"); compare them
 * with Money::centavos(), never as floats. Patterns are stored without
 * delimiters (e.g. `^\d{8}$`); use matchesPattern() to test a value.
 */
final class PropertySettings
{
    public const ENTITY_NAME = 'property.entity_name';

    public const ENTITY_ADDRESS = 'property.entity_address';

    public const PPE_THRESHOLD = 'property.ppe_threshold';

    public const SPLV_THRESHOLD = 'property.splv_threshold';

    public const ICS_TAGGING = 'property.ics_tagging';

    public const PAPER_SIZE = 'property.paper_size';

    public const RENEWAL_YEARS = 'property.renewal_years';

    public const RIS_PATTERN = 'property.ris_pattern';

    public const ICS_PATTERN = 'property.ics_pattern';

    public const PAR_PATTERN = 'property.par_pattern';

    /**
     * Values used until a setting is saved.
     *
     * @var array<string, string|int>
     */
    public const DEFAULTS = [
        self::ENTITY_NAME => 'Governor Benjamin T. Romualdez General Hospital and Schistosomiasis Center',
        self::ENTITY_ADDRESS => 'Palo, Leyte',
        self::PPE_THRESHOLD => '50000.00',
        self::SPLV_THRESHOLD => '5000.00',
        self::ICS_TAGGING => 'all',
        self::PAPER_SIZE => 'a4',
        self::RENEWAL_YEARS => 3,
        self::RIS_PATTERN => '^\d{8}$',
        self::ICS_PATTERN => '^(SPLV|SPHV)-\d{4}-\d{2}-\d{3}$',
        self::PAR_PATTERN => '^\d{4}-\d{2}-\d{3}$',
    ];

    public static function entityName(): string
    {
        return (string) self::get(self::ENTITY_NAME);
    }

    public static function setEntityName(string $name): void
    {
        self::set(self::ENTITY_NAME, $name);
    }

    public static function entityAddress(): string
    {
        return (string) self::get(self::ENTITY_ADDRESS);
    }

    public static function setEntityAddress(string $address): void
    {
        self::set(self::ENTITY_ADDRESS, $address);
    }

    /**
     * Unit cost at or above which a property unit is PPE (goes on a PAR).
     */
    public static function ppeThreshold(): string
    {
        return self::amount(self::PPE_THRESHOLD);
    }

    public static function setPpeThreshold(string|float|int $amount): void
    {
        self::set(self::PPE_THRESHOLD, Money::fromCentavos(Money::centavos($amount)));
    }

    /**
     * Unit cost at or below which a property unit is low-value semi-expendable (SPLV).
     */
    public static function splvThreshold(): string
    {
        return self::amount(self::SPLV_THRESHOLD);
    }

    public static function setSplvThreshold(string|float|int $amount): void
    {
        self::set(self::SPLV_THRESHOLD, Money::fromCentavos(Money::centavos($amount)));
    }

    public static function icsTagging(): IcsTagging
    {
        return IcsTagging::tryFrom((string) self::get(self::ICS_TAGGING))
            ?? IcsTagging::from((string) self::DEFAULTS[self::ICS_TAGGING]);
    }

    public static function setIcsTagging(IcsTagging $tagging): void
    {
        self::set(self::ICS_TAGGING, $tagging->value);
    }

    public static function paperSize(): PaperSize
    {
        return PaperSize::tryFrom((string) self::get(self::PAPER_SIZE))
            ?? PaperSize::from((string) self::DEFAULTS[self::PAPER_SIZE]);
    }

    public static function setPaperSize(PaperSize $size): void
    {
        self::set(self::PAPER_SIZE, $size->value);
    }

    /**
     * Years after an ICS/PAR's document date when it is due for renewal.
     */
    public static function renewalYears(): int
    {
        return (int) self::get(self::RENEWAL_YEARS);
    }

    public static function setRenewalYears(int $years): void
    {
        self::set(self::RENEWAL_YEARS, $years);
    }

    public static function risPattern(): string
    {
        return (string) self::get(self::RIS_PATTERN);
    }

    public static function setRisPattern(string $pattern): void
    {
        self::set(self::RIS_PATTERN, $pattern);
    }

    public static function icsPattern(): string
    {
        return (string) self::get(self::ICS_PATTERN);
    }

    public static function setIcsPattern(string $pattern): void
    {
        self::set(self::ICS_PATTERN, $pattern);
    }

    public static function parPattern(): string
    {
        return (string) self::get(self::PAR_PATTERN);
    }

    public static function setParPattern(string $pattern): void
    {
        self::set(self::PAR_PATTERN, $pattern);
    }

    /**
     * Whether a delimiter-less pattern compiles as a PCRE regex.
     */
    public static function isValidPattern(string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        set_error_handler(fn (): bool => true);

        try {
            return preg_match(self::delimit($pattern), '') !== false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Whether a value matches a delimiter-less pattern. An invalid pattern
     * matches nothing.
     */
    public static function matchesPattern(string $pattern, string $value): bool
    {
        return self::isValidPattern($pattern) && preg_match(self::delimit($pattern), $value) === 1;
    }

    private static function get(string $key): mixed
    {
        return InventoryCache::settings()[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    private static function set(string $key, string|int $value): void
    {
        Settings::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private static function amount(string $key): string
    {
        return Money::fromCentavos(Money::centavos((string) self::get($key)));
    }

    private static function delimit(string $pattern): string
    {
        return '~'.str_replace('~', '\~', $pattern).'~u';
    }
}
