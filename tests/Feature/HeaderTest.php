<?php

use App\Models\User;
use App\Support\Breadcrumbs;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['name' => 'Jane Clerk']));
});

test('dashboard breadcrumb is a single current crumb', function () {
    $trail = Breadcrumbs::for('dashboard');

    expect($trail)->toHaveCount(1)
        ->and($trail[0]['label'])->toBe('Dashboard')
        ->and($trail[0]['url'])->toBeNull();
});

test('section pages get home, a section dropdown and the page', function () {
    $trail = Breadcrumbs::for('inventory.adjustments');

    expect(array_column($trail, 'label'))->toBe(['Dashboard', 'Inventory', 'Adjustments'])
        ->and($trail[0]['url'])->toBe(route('dashboard'))
        ->and(array_column($trail[1]['pages'], 'label'))
        ->toBe(['Inventory Stock', 'Receiving', 'Adjustments', 'Items', 'Categories', 'Suppliers'])
        ->and(collect($trail[1]['pages'])->firstWhere('current')['label'])->toBe('Adjustments');
});

test('unmapped routes fall back to a readable label', function () {
    expect(array_column(Breadcrumbs::for('reports.monthly-summary'), 'label'))
        ->toBe(['Dashboard', 'Monthly Summary']);
});

test('every mapped page points at a real route', function () {
    foreach (config('navigation.sections') as $section) {
        foreach (array_keys($section['pages']) as $route) {
            expect(Route::has($route))->toBeTrue("Missing route [$route] in config/navigation.php");
        }
    }
});

test('profile menu links to the homepage from app pages', function () {
    $this->get(route('inventory.stocks'))
        ->assertOk()
        ->assertSee('data-test="menu-home-link"', false)
        ->assertDontSee('data-test="menu-dashboard-link"', false);
});

test('profile menu links to the dashboard from the homepage', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-test="menu-dashboard-link"', false)
        ->assertDontSee('data-test="menu-home-link"', false);
});

test('header shows breadcrumbs, theme toggle and profile menu', function () {
    $this->get(route('inventory.stocks'))
        ->assertOk()
        ->assertSee('data-flux-breadcrumbs', false)
        ->assertSee('Inventory Stock')
        ->assertSee('Toggle light / dark mode')
        ->assertSee('data-test="header-menu-button"', false)
        ->assertSee('Jane Clerk')
        ->assertDontSee('data-test="sidebar-menu-button"', false);
});
