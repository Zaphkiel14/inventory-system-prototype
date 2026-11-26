<?php

namespace App\Services\AdministrativeAreas;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdministrativeAreaService
{
    protected const CACHE_KEY = 'administrative-areas';

    /**
     * The areas pinned while options()/groupedOptions() build their lists, so the
     * label lookups inside (find() per shared name) don't re-read the cache.
     */
    protected ?Collection $areas = null;

    public function all(): Collection
    {
        // Cache a plain array: config/cache.php disallows unserializing
        // objects (serializable_classes = false), so a cached Collection
        // would come back as __PHP_Incomplete_Class.
        return $this->areas ?? collect(Cache::remember(
            self::CACHE_KEY,
            now()->addHours(6),
            fn () => $this->request()->all()
        ));
    }

    /**
     * Run a callback with the area list read from the cache once.
     *
     * @template TResult
     *
     * @param  callable(Collection): TResult  $callback
     * @return TResult
     */
    protected function withAreas(callable $callback): mixed
    {
        if ($this->areas !== null) {
            return $callback($this->areas);
        }

        $this->areas = $this->all();

        try {
            return $callback($this->areas);
        } finally {
            $this->areas = null;
        }
    }

    protected function request(): Collection
    {
        // ADMINISTRATIVE_AREAS_API_URL is the full list endpoint
        // (GET /api/administrative-areas), not just the host.
        $response = Http::acceptJson()
            ->timeout(10)
            ->withOptions(['verify' => $this->verifyOption()])
            ->get(config('services.administrative_areas.url'));

        $response->throw();

        return collect($response->json('data', []));
    }

    /**
     * TLS verification for the intranet API, from
     * services.administrative_areas.verify (ADMINISTRATIVE_AREAS_SSL_VERIFY):
     * - a path to a CA bundle (PEM) that trusts the internal CA (recommended)
     * - true to use the system/php.ini CA bundle (default)
     * - false to skip verification (temporary workaround only)
     */
    protected function verifyOption(): bool|string
    {
        $verify = config('services.administrative_areas.verify', true);

        if (is_string($verify)) {
            // "true"/"false"/"0"/"1" from .env become booleans; anything else is a CA path.
            return filter_var($verify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $verify;
        }

        return (bool) $verify;
    }

    public function find(int $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }

    /**
     * Names (lowercased, trimmed) used by more than one area, memoized.
     *
     * @var array<string, bool>|null
     */
    protected ?array $sharedNames = null;

    /**
     * Human-readable label, e.g. "Accounting Section (AS) · Section".
     * The parent is left out, except when another area has the same name:
     * "Emergency Unit (EU) · Unit · Nursing Service Division".
     */
    public function label(array $area): string
    {
        return collect([
            $this->shortLabel($area),
            filled($area['type'] ?? null) ? ucfirst($area['type']) : null,
            $this->parentSuffix($area),
        ])->filter()->implode(' · ');
    }

    /**
     * Every area as an [id => label] map, sorted by name, for pickers.
     *
     * @return Collection<int, string>
     */
    public function options(): Collection
    {
        return $this->withAreas(fn (Collection $areas) => $areas
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (array $area) => [$area['id'] => $this->label($area)]));
    }

    /**
     * Areas grouped for a select's option groups: one group per top-level area
     * (named after it), holding that area first and then all of its descendants,
     * indented by depth. Areas whose parent is missing from the API go in "Other".
     *
     * @return Collection<int, array{label: string, options: Collection<int, string>}>
     */
    public function groupedOptions(): Collection
    {
        return $this->withAreas(fn (Collection $areas) => $this->groupAreas($areas));
    }

    /**
     * @return Collection<int, array{label: string, options: Collection<int, string>}>
     */
    protected function groupAreas(Collection $areas): Collection
    {
        $byParent = $areas->groupBy(fn (array $area) => $area['parent_id'] ?? 'root');
        $seen = [];

        $flatten = function (array $area, int $depth) use (&$flatten, &$seen, $byParent): array {
            if (isset($seen[$area['id']])) {
                return [];
            }

            $seen[$area['id']] = true;

            $rows = [$area['id'] => str_repeat('— ', $depth).$this->optionLabel($area)];

            foreach ($this->sortByName($byParent->get($area['id'], collect())) as $child) {
                $rows += $flatten($child, $depth + 1);
            }

            return $rows;
        };

        $groups = $this->sortByName($byParent->get('root', collect()))
            ->map(fn (array $root) => [
                'label' => $this->shortLabel($root),
                'options' => collect($flatten($root, 0)),
            ]);

        $orphans = $this->sortByName($areas->reject(fn (array $area) => isset($seen[$area['id']])));

        if ($orphans->isNotEmpty()) {
            $groups->push([
                'label' => 'Other',
                'options' => $orphans->mapWithKeys(fn (array $area) => [$area['id'] => $this->optionLabel($area)]),
            ]);
        }

        return $groups->values();
    }

    /** Name with abbreviation, e.g. "Accounting Section (AS)"; the group supplies the parent context. */
    protected function shortLabel(array $area): string
    {
        return $area['name'].(filled($area['abbreviation'] ?? null) ? " ({$area['abbreviation']})" : '');
    }

    /**
     * A picker option: the short label, plus the parent when the name is shared,
     * so the selected value stays unambiguous once the dropdown closes.
     *
     * @param  array<string, mixed>  $area
     */
    protected function optionLabel(array $area): string
    {
        $parent = $this->parentSuffix($area);

        return $this->shortLabel($area).($parent !== null ? " · {$parent}" : '');
    }

    /**
     * The parent's name, but only when another area has the same name.
     *
     * @param  array<string, mixed>  $area
     */
    protected function parentSuffix(array $area): ?string
    {
        if (! isset($this->sharedNames()[$this->normalizeName($area['name'] ?? '')], $area['parent_id'])) {
            return null;
        }

        return $this->find((int) $area['parent_id'])['name'] ?? null;
    }

    /** @return array<string, bool> */
    protected function sharedNames(): array
    {
        return $this->sharedNames ??= $this->all()
            ->countBy(fn (array $area) => $this->normalizeName($area['name'] ?? ''))
            ->filter(fn (int $count) => $count > 1)
            ->map(fn () => true)
            ->all();
    }

    protected function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    protected function sortByName(Collection $areas): Collection
    {
        return $areas->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    public function children(?int $parentId): Collection
    {
        return $this->all()
            ->where('parent_id', $parentId)
            ->values();
    }

    public function byType(string $type): Collection
    {
        return $this->all()
            ->where('type', $type)
            ->values();
    }

    public function tree(): Collection
    {
        $areas = $this->all();

        return $this->buildTree($areas, null);
    }

    protected function buildTree(
        Collection $areas,
        ?int $parentId
    ): Collection {
        return $areas
            ->where('parent_id', $parentId)
            ->map(function ($area) use ($areas) {
                $area['children'] = $this->buildTree(
                    $areas,
                    $area['id']
                );

                return $area;
            })
            ->values();
    }

    public function refresh(): Collection
    {
        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }
}
