<?php

namespace App\Services\AdministrativeAreas;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AdministrativeAreaService
{
    protected const CACHE_KEY = 'administrative-areas';

    public function all(): Collection
    {
        // Cache a plain array: config/cache.php disallows unserializing
        // objects (serializable_classes = false), so a cached Collection
        // would come back as __PHP_Incomplete_Class.
        return collect(Cache::remember(
            self::CACHE_KEY,
            now()->addHours(6),
            fn () => $this->request()->all()
        ));
    }

    protected function request(): Collection
    {
        // ADMINISTRATIVE_AREAS_API_URL is the full list endpoint
        // (GET /api/administrative-areas), not just the host.
        $response = Http::acceptJson()
            ->timeout(10)
            ->get(config('services.administrative_areas.url'));

        $response->throw();

        return collect($response->json('data', []));
    }

    public function find(int $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }

    /**
     * Human-readable label, e.g. "Accounting Section (AS) · Section · Finance Office".
     * The type and parent tell apart areas with similar names.
     */
    public function label(array $area): string
    {
        $parent = isset($area['parent_id']) ? $this->find((int) $area['parent_id']) : null;

        return collect([
            $area['name'].(filled($area['abbreviation'] ?? null) ? " ({$area['abbreviation']})" : ''),
            filled($area['type'] ?? null) ? ucfirst($area['type']) : null,
            $parent['name'] ?? null,
        ])->filter()->implode(' · ');
    }

    /**
     * Every area as an [id => label] map, sorted by name, for pickers.
     *
     * @return Collection<int, string>
     */
    public function options(): Collection
    {
        return $this->all()
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->mapWithKeys(fn (array $area) => [$area['id'] => $this->label($area)]);
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
        $areas = $this->all();
        $byParent = $areas->groupBy(fn (array $area) => $area['parent_id'] ?? 'root');
        $seen = [];

        $flatten = function (array $area, int $depth) use (&$flatten, &$seen, $byParent): array {
            if (isset($seen[$area['id']])) {
                return [];
            }

            $seen[$area['id']] = true;

            $rows = [$area['id'] => str_repeat('— ', $depth).$this->shortLabel($area)];

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
                'options' => $orphans->mapWithKeys(fn (array $area) => [$area['id'] => $this->shortLabel($area)]),
            ]);
        }

        return $groups->values();
    }

    /** Name with abbreviation, e.g. "Accounting Section (AS)"; the group supplies the parent context. */
    protected function shortLabel(array $area): string
    {
        return $area['name'].(filled($area['abbreviation'] ?? null) ? " ({$area['abbreviation']})" : '');
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
