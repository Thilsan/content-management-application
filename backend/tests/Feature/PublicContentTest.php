<?php

use App\Models\Menu;
use App\Models\Page;

it('serves the public endpoints without a token', function (): void {
    $menu = Menu::factory()->create();
    Page::factory()->published()->create(['menu_id' => $menu->id]);

    $this->getJson('/api/public/menu')->assertOk();
    $this->getJson('/api/public/pages')->assertOk();
});

it('leaves drafts and future dated pages out of the menu', function (): void {
    $menu = Menu::factory()->create();

    $live = Page::factory()->published()->create(['menu_id' => $menu->id]);
    $draft = Page::factory()->draft()->create(['menu_id' => $menu->id]);
    $scheduled = Page::factory()->scheduled()->create(['menu_id' => $menu->id]);

    $titles = collect($this->getJson('/api/public/menu')->assertOk()->json('data.0.pages'))
        ->pluck('title')
        ->all();

    expect($titles)->toContain($live->title)
        ->and($titles)->not->toContain($draft->title)
        ->and($titles)->not->toContain($scheduled->title);
});

it('leaves drafts and future dated pages out of the page list', function (): void {
    $menu = Menu::factory()->create();

    Page::factory()->published()->create(['menu_id' => $menu->id]);
    Page::factory()->draft()->create(['menu_id' => $menu->id]);
    Page::factory()->scheduled()->create(['menu_id' => $menu->id]);

    $this->getJson('/api/public/pages')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1);
});

it('returns 404 for a draft page requested by slug', function (): void {
    $page = Page::factory()->draft()->create();

    $this->getJson("/api/public/pages/{$page->slug}")->assertNotFound();
});

it('shows a scheduled page only once the scheduler has promoted it', function (): void {
    $page = Page::factory()->scheduled('+3 days')->create();

    $this->getJson("/api/public/pages/{$page->slug}")->assertNotFound();

    $this->travel(4)->days();

    // The date passing is not enough on its own; the command owns the switch.
    $this->getJson("/api/public/pages/{$page->slug}")->assertNotFound();

    $this->artisan('pages:publish-due')->assertSuccessful();

    $this->getJson("/api/public/pages/{$page->slug}")
        ->assertOk()
        ->assertJsonPath('data.slug', $page->slug);
});

it('treats a published page with no date as live', function (): void {
    $page = Page::factory()->create(['status' => 'published', 'published_at' => null]);

    $this->getJson("/api/public/pages/{$page->slug}")
        ->assertOk()
        ->assertJsonPath('data.is_visible', true);
});

it('hides everything filed under an inactive menu item', function (): void {
    $menu = Menu::factory()->inactive()->create();
    $page = Page::factory()->published()->create(['menu_id' => $menu->id]);

    $this->getJson('/api/public/menu')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/public/pages')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/public/pages/{$page->slug}")->assertNotFound();
});

it('hides a branch when its parent is switched off', function (): void {
    $parent = Menu::factory()->inactive()->create();
    $child = Menu::factory()->childOf($parent)->create();
    Page::factory()->published()->create(['menu_id' => $child->id]);

    $this->getJson('/api/public/menu')->assertOk()->assertJsonCount(0, 'data');
});

it('filters the public page list by menu slug', function (): void {
    $news = Menu::factory()->create(['slug' => 'news']);
    $about = Menu::factory()->create(['slug' => 'about']);

    Page::factory()->published()->create(['menu_id' => $news->id]);
    Page::factory()->published()->count(2)->create(['menu_id' => $about->id]);

    $this->getJson('/api/public/pages?menu=news')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/public/pages?menu=about')->assertOk()->assertJsonCount(2, 'data');
});

it('does not leak the page body into the public listing', function (): void {
    $menu = Menu::factory()->create();
    Page::factory()->published()->create([
        'menu_id' => $menu->id,
        'body' => '<p>Full article text that belongs on the detail page.</p>',
    ]);

    $listed = $this->getJson('/api/public/pages')->assertOk()->json('data.0');

    expect($listed)->not->toHaveKey('body')
        ->and($listed['excerpt'])->toBe('Full article text that belongs on the detail page.');
});
