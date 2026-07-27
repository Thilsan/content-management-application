<?php

use App\Models\Menu;
use App\Models\Page;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed([PrivilegeSeeder::class, RoleSeeder::class]);

    $this->admin = userWithRole('admin');

    Sanctum::actingAs($this->admin);
});

it('paginates the page list', function (): void {
    Page::factory()->count(7)->create();

    $this->getJson('/api/pages?per_page=3')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('meta.total', 7)
        ->assertJsonPath('meta.per_page', 3);
});

it('searches by title', function (): void {
    Page::factory()->create(['title' => 'Quarterly Financial Report', 'slug' => 'quarterly-financial-report']);
    Page::factory()->create(['title' => 'Team Offsite Notes', 'slug' => 'team-offsite-notes']);

    $this->getJson('/api/pages?search=financial')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Quarterly Financial Report');
});

it('filters by menu and by status', function (): void {
    $menu = Menu::factory()->create();

    Page::factory()->draft()->create(['menu_id' => $menu->id]);
    Page::factory()->published()->create(['menu_id' => $menu->id]);
    Page::factory()->published()->create();

    $this->getJson("/api/pages?menu_id={$menu->id}")->assertJsonCount(2, 'data');
    $this->getJson("/api/pages?menu_id={$menu->id}&status=draft")->assertJsonCount(1, 'data');
    $this->getJson('/api/pages?status=published')->assertJsonCount(2, 'data');
});

it('rejects an unknown status filter', function (): void {
    $this->getJson('/api/pages?status=archived')
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('validates the page payload', function (): void {
    $this->postJson('/api/pages', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['menu_id', 'title', 'body', 'status']);
});

it('stores a page with a cover image and records the author', function (): void {
    Storage::fake('public');

    $menu = Menu::factory()->create();

    $response = $this->post('/api/pages', [
        'menu_id' => $menu->id,
        'title' => 'Annual Report 2026',
        'body' => '<p>The year in review.</p>',
        'status' => 'published',
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 630),
    ], ['Accept' => 'application/json'])->assertCreated();

    $page = Page::firstWhere('slug', 'annual-report-2026');

    expect($page)->not->toBeNull()
        ->and($page->created_by)->toBe($this->admin->id)
        ->and($page->updated_by)->toBe($this->admin->id);

    Storage::disk('public')->assertExists($page->cover_image);
    expect($response->json('data.cover_image_url'))->toContain($page->cover_image);
});

it('rejects a cover image that is not an image', function (): void {
    Storage::fake('public');

    $this->post('/api/pages', [
        'menu_id' => Menu::factory()->create()->id,
        'title' => 'Bad Cover',
        'body' => '<p>x</p>',
        'status' => 'draft',
        'cover_image' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
    ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('cover_image');
});

it('records the editor without losing the original author', function (): void {
    $page = Page::factory()->create();
    $moderator = userWithRole('moderator');

    Sanctum::actingAs($moderator);

    $this->putJson("/api/pages/{$page->id}", ['body' => '<p>Revised copy.</p>'])->assertOk();

    expect($page->fresh()->created_by)->toBe($this->admin->id)
        ->and($page->fresh()->updated_by)->toBe($moderator->id);
});

it('keeps the slug when only the title changes', function (): void {
    $page = Page::factory()->create(['title' => 'Original Title', 'slug' => 'original-title']);

    $this->putJson("/api/pages/{$page->id}", ['title' => 'A Completely New Title'])->assertOk();

    expect($page->fresh()->slug)->toBe('original-title');
});

it('generates a distinct slug when a title repeats', function (): void {
    $menu = Menu::factory()->create();

    $payload = [
        'menu_id' => $menu->id,
        'title' => 'Terms of Service',
        'body' => '<p>Terms.</p>',
        'status' => 'draft',
    ];

    $this->postJson('/api/pages', $payload)->assertCreated()->assertJsonPath('data.slug', 'terms-of-service');
    $this->postJson('/api/pages', $payload)->assertCreated()->assertJsonPath('data.slug', 'terms-of-service-2');
});

it('moves a page to the trash and restores it', function (): void {
    $page = Page::factory()->create();

    $this->deleteJson("/api/pages/{$page->id}")->assertOk();

    $this->getJson('/api/pages')->assertJsonCount(0, 'data');
    $this->getJson('/api/pages/trashed')->assertJsonCount(1, 'data');

    $this->postJson("/api/pages/{$page->id}/restore")->assertOk();

    $this->getJson('/api/pages')->assertJsonCount(1, 'data');
    $this->getJson('/api/pages/trashed')->assertJsonCount(0, 'data');
});

it('removes the cover file when a page is deleted for good', function (): void {
    Storage::fake('public');

    $this->post('/api/pages', [
        'menu_id' => Menu::factory()->create()->id,
        'title' => 'Temporary Notice',
        'body' => '<p>Short lived.</p>',
        'status' => 'draft',
        'cover_image' => UploadedFile::fake()->image('notice.png'),
    ], ['Accept' => 'application/json'])->assertCreated();

    $page = Page::firstWhere('slug', 'temporary-notice');
    $cover = $page->cover_image;

    $this->deleteJson("/api/pages/{$page->id}")->assertOk();
    Storage::disk('public')->assertExists($cover);

    $this->deleteJson("/api/pages/{$page->id}/force")->assertOk();

    Storage::disk('public')->assertMissing($cover);
    expect(Page::withTrashed()->find($page->id))->toBeNull();
});

it('exposes who created and last edited a page in the listing', function (): void {
    Page::factory()->create();

    $this->getJson('/api/pages')
        ->assertOk()
        ->assertJsonPath('data.0.created_by.name', $this->admin->name)
        ->assertJsonPath('data.0.updated_by.name', $this->admin->name);
});
