<?php

namespace Database\Seeders;

use App\Enums\PageStatus;
use App\Models\Menu;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->createMenus($this->menuTree(), null);

        $author = User::where('email', 'admin@cms.test')->firstOrFail();

        foreach ($this->pages() as $data) {
            $menu = Menu::where('slug', $data['menu'])->firstOrFail();

            Page::withTrashed()
                ->firstOrNew(['slug' => $data['slug']])
                ->forceFill([
                    'menu_id' => $menu->id,
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'status' => $data['status'],
                    'published_at' => $data['published_at'],
                    'position' => $data['position'],
                    'created_by' => $author->id,
                    'updated_by' => $author->id,
                ])
                ->save();
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function createMenus(array $items, ?int $parentId): void
    {
        foreach ($items as $position => $item) {
            $menu = Menu::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'title' => $item['title'],
                    'parent_id' => $parentId,
                    'position' => $position,
                    'is_active' => true,
                ],
            );

            $this->createMenus($item['children'] ?? [], $menu->id);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function menuTree(): array
    {
        return [
            [
                'title' => 'About',
                'slug' => 'about',
                'children' => [
                    ['title' => 'Our Team', 'slug' => 'our-team'],
                    ['title' => 'Careers', 'slug' => 'careers'],
                ],
            ],
            [
                'title' => 'Services',
                'slug' => 'services',
                'children' => [
                    ['title' => 'Consulting', 'slug' => 'consulting'],
                ],
            ],
            [
                'title' => 'News',
                'slug' => 'news',
            ],
        ];
    }

    /**
     * Demo content. The last two entries exist so the draft and scheduled rules
     * are visible on the public site straight after seeding: neither should be
     * listed, and the scheduled one appears on its own once its date passes.
     *
     * @return list<array<string, mixed>>
     */
    private function pages(): array
    {
        return [
            [
                'menu' => 'about',
                'title' => 'Who We Are',
                'slug' => 'who-we-are',
                'status' => PageStatus::Published,
                'published_at' => now()->subMonths(2),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>A short history</h2>
                    <p>We started in 2014 with three people and a single product idea. Today we help
                    organisations across the region put their content in front of the people who need it.</p>
                    <p>Our work sits in three areas: strategy, delivery and long term support. Each one is
                    handled by a small team that stays with a client from the first workshop onwards.</p>
                    <ul>
                        <li>Offices in Colombo and Dubai</li>
                        <li>Forty two people across engineering, design and delivery</li>
                        <li>Clients in retail, logistics and public services</li>
                    </ul>
                    HTML,
            ],
            [
                'menu' => 'our-team',
                'title' => 'Leadership',
                'slug' => 'leadership',
                'status' => PageStatus::Published,
                'published_at' => now()->subMonth(),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>The people running things</h2>
                    <p>Our leadership team is deliberately small. Everyone still spends part of the week
                    on client work, which keeps the decisions close to the delivery.</p>
                    <p>The team meets every Monday to review the roadmap and again on Thursday to look at
                    anything that slipped. Notes from both meetings go out to the whole company.</p>
                    HTML,
            ],
            [
                'menu' => 'careers',
                'title' => 'Open Positions',
                'slug' => 'open-positions',
                'status' => PageStatus::Published,
                'published_at' => now()->subWeeks(3),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>Roles we are hiring for</h2>
                    <p>We hire a handful of people each year and take our time over it. If nothing below
                    matches what you do, send us a note anyway and we will keep it on file.</p>
                    <ul>
                        <li><strong>Backend engineer</strong> &mdash; PHP and Laravel, Colombo or remote</li>
                        <li><strong>Frontend engineer</strong> &mdash; React and TypeScript, Colombo</li>
                        <li><strong>Delivery lead</strong> &mdash; Dubai</li>
                    </ul>
                    <p>Every application gets a reply, usually within a week.</p>
                    HTML,
            ],
            [
                'menu' => 'services',
                'title' => 'What We Do',
                'slug' => 'what-we-do',
                'status' => PageStatus::Published,
                'published_at' => now()->subMonths(4),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>Our services</h2>
                    <p>We take on work in three shapes: a fixed scope build, a longer running delivery
                    team, or a short review of something that is already live.</p>
                    <p>Most engagements start with a two week discovery so both sides know what is being
                    signed up for before any code is written.</p>
                    HTML,
            ],
            [
                'menu' => 'consulting',
                'title' => 'Advisory Services',
                'slug' => 'advisory-services',
                'status' => PageStatus::Published,
                'published_at' => now()->subWeeks(6),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>Advisory</h2>
                    <p>Sometimes a team does not need more hands, it needs a second opinion. Our advisory
                    work covers architecture reviews, hiring plans and delivery audits.</p>
                    <p>Engagements run from a single day workshop to a standing monthly review.</p>
                    HTML,
            ],
            [
                'menu' => 'news',
                'title' => 'Company Update',
                'slug' => 'company-update',
                'status' => PageStatus::Published,
                'published_at' => now()->subDays(5),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>Where things stand</h2>
                    <p>The first half of the year closed ahead of plan. Two new clients came on board and
                    the support team grew by four people.</p>
                    <p>The next update goes out at the end of the quarter.</p>
                    HTML,
            ],
            [
                'menu' => 'careers',
                'title' => 'Internship Programme',
                'slug' => 'internship-programme',
                'status' => PageStatus::Draft,
                'published_at' => null,
                'position' => 1,
                'body' => <<<'HTML'
                    <h2>Internships</h2>
                    <p>Still being written. This page is a draft, so it is listed in the back end but the
                    public site does not show it.</p>
                    HTML,
            ],
            [
                'menu' => 'news',
                'title' => 'Autumn Product Launch',
                'slug' => 'autumn-product-launch',
                'status' => PageStatus::Published,
                'published_at' => now()->addWeek(),
                'position' => 1,
                'body' => <<<'HTML'
                    <h2>Launch announcement</h2>
                    <p>This page is published but dated a week ahead, so the public site keeps it hidden
                    until that date passes. The back end shows it as scheduled.</p>
                    HTML,
            ],
        ];
    }
}
