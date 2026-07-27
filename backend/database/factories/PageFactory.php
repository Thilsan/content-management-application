<?php

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Menu;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'menu_id' => Menu::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'body' => collect(fake()->paragraphs(3))
                ->map(fn (string $paragraph) => "<p>{$paragraph}</p>")
                ->implode("\n"),
            'cover_image' => null,
            'status' => PageStatus::Published,
            'published_at' => now()->subDay(),
            'position' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => now()->subDay(),
        ]);
    }

    /**
     * Published, but with a publish date that has not arrived yet.
     */
    public function scheduled(?string $when = null): static
    {
        return $this->state(fn () => [
            'status' => PageStatus::Published,
            'published_at' => $when ? now()->parse($when) : now()->addWeek(),
        ]);
    }

    public function authoredBy(User $user): static
    {
        return $this->afterMaking(function (Page $page) use ($user): void {
            $page->created_by = $user->id;
            $page->updated_by = $user->id;
        });
    }
}
