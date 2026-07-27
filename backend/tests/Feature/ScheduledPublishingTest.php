<?php

use App\Models\Page;
use Illuminate\Console\Scheduling\Schedule;

it('promotes a page once its publish date has passed', function (): void {
    $page = Page::factory()->scheduled('+2 days')->create();

    expect($page->is_live)->toBeFalse();

    $this->artisan('pages:publish-due')->assertSuccessful();
    expect($page->fresh()->is_live)->toBeFalse();

    $this->travel(3)->days();

    $this->artisan('pages:publish-due')->assertSuccessful();
    expect($page->fresh()->is_live)->toBeTrue();
});

it('leaves a draft alone however long it waits', function (): void {
    $page = Page::factory()->draft()->create();

    $this->travel(1)->year();
    $this->artisan('pages:publish-due')->assertSuccessful();

    expect($page->fresh()->is_live)->toBeFalse();
});

it('withdraws a page that has been pulled back to draft', function (): void {
    $page = Page::factory()->published()->create();

    expect($page->is_live)->toBeTrue();

    // Straight to the database, so the model hook does not do the work first.
    Page::withoutEvents(fn () => $page->update(['status' => 'draft']));

    $this->artisan('pages:publish-due')->assertSuccessful();

    expect($page->fresh()->is_live)->toBeFalse();
});

it('withdraws a page whose date has been pushed into the future', function (): void {
    $page = Page::factory()->published()->create();

    Page::withoutEvents(fn () => $page->update(['published_at' => now()->addWeek()]));

    $this->artisan('pages:publish-due')->assertSuccessful();

    expect($page->fresh()->is_live)->toBeFalse();
});

it('does not disturb the audit trail when it promotes a page', function (): void {
    $page = Page::factory()->scheduled('+2 days')->create();
    $editedAt = $page->updated_at;
    $editor = $page->updated_by;

    $this->travel(3)->days();
    $this->artisan('pages:publish-due')->assertSuccessful();

    $fresh = $page->fresh();

    expect($fresh->is_live)->toBeTrue()
        ->and($fresh->updated_by)->toBe($editor)
        ->and($fresh->updated_at->timestamp)->toBe($editedAt->timestamp);
});

it('publishes immediately when an editor saves with no date', function (): void {
    // Nothing should wait on the scheduler just to go live now.
    $page = Page::factory()->create(['status' => 'published', 'published_at' => null]);

    expect($page->is_live)->toBeTrue();
});

it('is registered on the scheduler', function (): void {
    $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command);

    expect($commands->filter(fn ($command) => str_contains((string) $command, 'pages:publish-due')))
        ->not->toBeEmpty();
});
