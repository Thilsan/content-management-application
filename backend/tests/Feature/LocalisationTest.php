<?php

use App\Models\Menu;
use App\Models\Page;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Laravel\Sanctum\Sanctum;

function arabicPage(array $attributes = []): Page
{
    return Page::factory()->published()->create([
        'title' => 'Who We Are',
        'title_ar' => 'من نحن',
        'body' => '<p>We started in 2014.</p>',
        'body_ar' => '<p>بدأنا في عام 2014.</p>',
        ...$attributes,
    ]);
}

it('serves English by default', function (): void {
    $page = arabicPage();

    $this->getJson("/api/public/pages/{$page->slug}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Who We Are')
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.direction', 'ltr');
});

it('serves Arabic when it is asked for', function (): void {
    $page = arabicPage();

    $this->getJson("/api/public/pages/{$page->slug}?lang=ar")
        ->assertOk()
        ->assertJsonPath('data.title', 'من نحن')
        ->assertJsonPath('data.body', '<p>بدأنا في عام 2014.</p>')
        ->assertJsonPath('data.locale', 'ar')
        ->assertJsonPath('data.direction', 'rtl')
        ->assertJsonPath('data.is_translated', true);
});

it('falls back to English on a page that has no Arabic', function (): void {
    $page = Page::factory()->published()->create([
        'title' => 'Open Positions',
        'title_ar' => null,
        'body_ar' => null,
    ]);

    // The reader asked for Arabic but this page has none, so it stays readable
    // in English and says so rather than returning an empty title.
    $this->getJson("/api/public/pages/{$page->slug}?lang=ar")
        ->assertOk()
        ->assertJsonPath('data.title', 'Open Positions')
        ->assertJsonPath('data.locale', 'en')
        ->assertJsonPath('data.direction', 'ltr')
        ->assertJsonPath('data.is_translated', false);
});

it('falls back for a half translated page', function (): void {
    $page = arabicPage(['body_ar' => null]);

    $this->getJson("/api/public/pages/{$page->slug}?lang=ar")
        ->assertOk()
        ->assertJsonPath('data.title', 'Who We Are')
        ->assertJsonPath('data.is_translated', false);
});

it('translates the menu tree', function (): void {
    $menu = Menu::factory()->create(['title' => 'About', 'title_ar' => 'من نحن']);
    arabicPage(['menu_id' => $menu->id]);

    $this->getJson('/api/public/menu?lang=ar')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'من نحن')
        ->assertJsonPath('data.0.pages.0.title', 'من نحن');

    $this->getJson('/api/public/menu')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'About');
});

it('keeps an untranslated menu item in English', function (): void {
    $menu = Menu::factory()->create(['title' => 'News', 'title_ar' => null]);
    arabicPage(['menu_id' => $menu->id]);

    $this->getJson('/api/public/menu?lang=ar')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'News');
});

it('lists pages in Arabic', function (): void {
    arabicPage();

    $this->getJson('/api/public/pages?lang=ar')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'من نحن')
        ->assertJsonPath('data.0.locale', 'ar');
});

it('searches Arabic titles too', function (): void {
    arabicPage();
    Page::factory()->published()->create(['title' => 'Something else', 'title_ar' => null]);

    $query = http_build_query(['lang' => 'ar', 'search' => 'نحن']);

    $this->getJson("/api/public/pages?{$query}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'من نحن');
});

it('rejects a language it does not support', function (): void {
    $this->getJson('/api/public/pages?lang=fr')
        ->assertStatus(422)
        ->assertJsonValidationErrors('lang');
});

it('gives the back office both languages to edit', function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);
    $page = arabicPage();

    Sanctum::actingAs(userWithRole('admin'));

    $this->getJson("/api/pages/{$page->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Who We Are')
        ->assertJsonPath('data.title_ar', 'من نحن')
        ->assertJsonPath('data.body_ar', '<p>بدأنا في عام 2014.</p>')
        ->assertJsonPath('data.is_translated', true);
});

it('saves Arabic content written in the back office', function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);
    $menu = Menu::factory()->create();

    Sanctum::actingAs(userWithRole('admin'));

    $this->postJson('/api/pages', [
        'menu_id' => $menu->id,
        'title' => 'Contact',
        'title_ar' => 'اتصل بنا',
        'body' => '<p>Reach us here.</p>',
        'body_ar' => '<p>تواصل معنا هنا.</p>',
        'status' => 'published',
    ])->assertCreated();

    $page = Page::firstWhere('slug', 'contact');

    expect($page->title_ar)->toBe('اتصل بنا')
        ->and($page->titleIn('ar'))->toBe('اتصل بنا')
        ->and($page->titleIn('en'))->toBe('Contact');
});
