<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Builds the header breadcrumb trail from the current route name and the
 * map in config/navigation.php.
 */
class Breadcrumbs
{
    /**
     * @return array<int, array{label: string, url: ?string, icon: ?string, pages: array<int, array{label: string, url: string, current: bool}>}>
     */
    public static function current(): array
    {
        return static::for(Route::currentRouteName());
    }

    /**
     * @return array<int, array{label: string, url: ?string, icon: ?string, pages: array<int, array{label: string, url: string, current: bool}>}>
     */
    public static function for(?string $routeName): array
    {
        $home = config('navigation.home');

        $trail = [static::crumb(
            label: $home['label'],
            url: $routeName === $home['route'] ? null : static::url($home['route']),
            icon: $home['icon'] ?? null,
        )];

        if ($routeName === null || $routeName === $home['route']) {
            return $trail;
        }

        foreach (config('navigation.sections', []) as $section) {
            if (! array_key_exists($routeName, $section['pages'])) {
                continue;
            }

            $pages = collect($section['pages'])
                ->filter(fn ($_, string $name) => Route::has($name))
                ->map(fn (string $label, string $name) => [
                    'label' => $label,
                    'url' => route($name),
                    'current' => $name === $routeName,
                ])
                ->values()
                ->all();

            $trail[] = static::crumb(label: $section['label'], pages: $pages);
            $trail[] = static::crumb(label: $section['pages'][$routeName]);

            return $trail;
        }

        // Route not in the map: fall back to a readable version of its name.
        $trail[] = static::crumb(label: Str::of($routeName)->afterLast('.')->replace(['-', '_'], ' ')->title());

        return $trail;
    }

    protected static function crumb(string $label, ?string $url = null, ?string $icon = null, array $pages = []): array
    {
        return compact('label', 'url', 'icon', 'pages');
    }

    protected static function url(string $routeName): ?string
    {
        return Route::has($routeName) ? route($routeName) : null;
    }
}
