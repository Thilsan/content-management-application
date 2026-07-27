<?php

use App\Models\Menu;
use App\Models\Page;

it('slugifies a title', function (): void {
    expect(unique_slug('About Our Company', 'menus'))->toBe('about-our-company');
});

it('appends a counter until the slug is free', function (): void {
    Menu::factory()->create(['slug' => 'about-us']);

    expect(unique_slug('About Us', 'menus'))->toBe('about-us-2');

    Menu::factory()->create(['slug' => 'about-us-2']);

    expect(unique_slug('About Us', 'menus'))->toBe('about-us-3');
});

it('lets a row keep the slug it already owns', function (): void {
    $menu = Menu::factory()->create(['slug' => 'about-us']);

    expect(unique_slug('About Us', 'menus', $menu->id))->toBe('about-us');
});

it('counts trashed rows so a slug is never quietly reused', function (): void {
    $page = Page::factory()->create(['slug' => 'retired-notice']);
    $page->delete();

    expect(unique_slug('Retired Notice', 'pages'))->toBe('retired-notice-2');
});

it('falls back when a title has nothing to slugify', function (): void {
    expect(unique_slug('!!!', 'menus'))->toBe('item');
});
