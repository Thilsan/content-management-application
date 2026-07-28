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

        $author = User::where('email', 'admin@cms.com')->firstOrFail();

        foreach ($this->pages() as $data) {
            $menu = Menu::where('slug', $data['menu'])->firstOrFail();

            Page::withTrashed()
                ->firstOrNew(['slug' => $data['slug']])
                ->forceFill([
                    'menu_id' => $menu->id,
                    'title' => $data['title'],
                    'title_ar' => $data['title_ar'] ?? null,
                    'body' => $data['body'],
                    'body_ar' => $data['body_ar'] ?? null,
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
                    'title_ar' => $item['title_ar'] ?? null,
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
                'title_ar' => 'من نحن',
                'slug' => 'about',
                'children' => [
                    ['title' => 'Our Team', 'title_ar' => 'فريقنا', 'slug' => 'our-team'],
                    ['title' => 'Careers', 'title_ar' => 'الوظائف', 'slug' => 'careers'],
                ],
            ],
            [
                'title' => 'Services',
                'title_ar' => 'خدماتنا',
                'slug' => 'services',
                'children' => [
                    ['title' => 'Consulting', 'title_ar' => 'الاستشارات', 'slug' => 'consulting'],
                ],
            ],
            [
                'title' => 'News',
                'title_ar' => 'الأخبار',
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
                'title_ar' => 'من نحن',
                'slug' => 'who-we-are',
                'status' => PageStatus::Published,
                'published_at' => now()->subMonths(2),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>A short history</h2>
                    <p>We started in 2014 with three people and a single product idea. Today we help
                    organisations across the region put their content in front of the people who need it.</p>
                    <p>The first few years were spent almost entirely on one client. That turned out to be
                    a good way to learn the business, and a poor way to build one, so we spent 2017
                    deliberately widening the base.</p>
                    <h2>How the work is organised</h2>
                    <p>Our work sits in three areas: strategy, delivery and long term support. Each one is
                    handled by a small team that stays with a client from the first workshop onwards, rather
                    than handing the work down a chain of people who were not in the room.</p>
                    <ul>
                        <li>Offices in Colombo and Dubai</li>
                        <li>Forty two people across engineering, design and delivery</li>
                        <li>Clients in retail, logistics and public services</li>
                    </ul>
                    <h2>What we care about</h2>
                    <p>Two things, mostly. That the people doing the work talk to the people who asked for
                    it, and that whatever we hand over can be maintained by someone who has never met us.</p>
                    <blockquote><p>The best sign a project went well is that nobody needs to call us about
                    it a year later.</p></blockquote>
                    <h2>Getting in touch</h2>
                    <p>Most conversations start with a short call and a look at what already exists. There
                    is no charge for that, and no expectation of anything afterwards.</p>
                    HTML,
                'body_ar' => <<<'HTML'
                    <h2>لمحة عن تاريخنا</h2>
                    <p>بدأنا في عام 2014 بثلاثة أشخاص وفكرة منتج واحدة. واليوم نساعد المؤسسات في المنطقة
                    على إيصال محتواها إلى من يحتاجه.</p>
                    <p>قضينا السنوات الأولى مع عميل واحد تقريبًا. كانت تلك طريقة جيدة لتعلّم المجال،
                    وطريقة سيئة لبناء شركة، فخصصنا عام 2017 لتوسيع قاعدة عملائنا.</p>
                    <h2>كيف ننظّم عملنا</h2>
                    <p>ينقسم عملنا إلى ثلاثة مجالات: الاستراتيجية والتنفيذ والدعم طويل الأمد. يتولى كل
                    مجال فريق صغير يبقى مع العميل من الورشة الأولى وحتى النهاية.</p>
                    <ul>
                        <li>مكاتب في كولومبو ودبي</li>
                        <li>اثنان وأربعون شخصًا في الهندسة والتصميم والتنفيذ</li>
                        <li>عملاء في التجزئة والخدمات اللوجستية والقطاع العام</li>
                    </ul>
                    <h2>ما الذي يهمنا</h2>
                    <p>أمران أساسًا: أن يتحدث من ينفّذ العمل مع من طلبه، وأن يكون ما نسلّمه قابلًا
                    للصيانة من شخص لم يلتقِ بنا قط.</p>
                    <blockquote><p>أفضل دليل على نجاح المشروع أن لا يحتاج أحد للاتصال بنا بشأنه بعد
                    عام.</p></blockquote>
                    HTML,
            ],
            [
                'menu' => 'our-team',
                'title' => 'Leadership',
                'title_ar' => 'القيادة',
                'slug' => 'leadership',
                'status' => PageStatus::Published,
                'published_at' => now()->subMonth(),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>The people running things</h2>
                    <p>Our leadership team is deliberately small. Everyone still spends part of the week
                    on client work, which keeps the decisions close to the delivery.</p>
                    <p>There are four of them, covering delivery, engineering, commercial and operations.
                    Nobody carries a title longer than two words.</p>
                    <h2>How decisions get made</h2>
                    <p>The team meets every Monday to review the roadmap and again on Thursday to look at
                    anything that slipped. Notes from both meetings go out to the whole company the same
                    afternoon, including the parts that did not go well.</p>
                    <p>Anything that affects a client is decided with the delivery lead for that account in
                    the room, not afterwards.</p>
                    <h2>Talking to them</h2>
                    <p>Every member of the team keeps two hours a week free for anyone in the company to
                    book, no agenda required.</p>
                    HTML,
                'body_ar' => <<<'HTML'
                    <h2>الفريق الذي يدير العمل</h2>
                    <p>فريق القيادة لدينا صغير عن قصد. لا يزال كل فرد فيه يقضي جزءًا من أسبوعه في العمل
                    مع العملاء، ما يبقي القرارات قريبة من التنفيذ.</p>
                    <h2>كيف تُتخذ القرارات</h2>
                    <p>يجتمع الفريق كل اثنين لمراجعة خطة العمل، ومرة أخرى يوم الخميس لمتابعة ما تأخر.
                    تُرسل ملاحظات الاجتماعين إلى الشركة كلها في اليوم نفسه.</p>
                    <h2>التواصل معهم</h2>
                    <p>يخصص كل عضو ساعتين أسبوعيًا يمكن لأي موظف حجزهما دون جدول أعمال.</p>
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
                    <h2>How the process runs</h2>
                    <p>Three conversations, usually across two weeks. A call to work out whether the role
                    is what you thought it was, a technical session built around the sort of work you would
                    actually do, and a longer chat with the team you would join.</p>
                    <p>There is no take home exercise that runs longer than an evening, and we pay for it
                    if it does.</p>
                    <h2>What we look for</h2>
                    <p>People who can explain a decision they later regretted, and what they did about it.
                    Almost everything else can be taught.</p>
                    <h2>Applying</h2>
                    <p>Every application gets a reply, usually within a week, whichever way it goes.</p>
                    HTML,
            ],
            [
                'menu' => 'services',
                'title' => 'What We Do',
                'title_ar' => 'ماذا نفعل',
                'slug' => 'what-we-do',
                'status' => PageStatus::Published,
                'published_at' => now()->subMonths(4),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>Our services</h2>
                    <p>We take on work in three shapes: a fixed scope build, a longer running delivery
                    team, or a short review of something that is already live.</p>
                    <h2>Fixed scope builds</h2>
                    <p>Best when the problem is well understood and the deadline matters more than the
                    flexibility. We quote a price and a date, and both hold unless the scope changes in
                    writing.</p>
                    <h2>Delivery teams</h2>
                    <p>A standing team of three to six people working to your roadmap. Most of our longer
                    relationships look like this, and a few have run for years.</p>
                    <h2>Reviews</h2>
                    <p>A week or two spent reading the code, talking to the team and writing down what we
                    found. Useful before a funding round, an acquisition, or a rebuild nobody is sure
                    about yet.</p>
                    <p>Most engagements start with a two week discovery so both sides know what is being
                    signed up for before any code is written.</p>
                    HTML,
                'body_ar' => <<<'HTML'
                    <h2>خدماتنا</h2>
                    <p>نعمل بثلاث صيغ: مشروع بنطاق محدد، أو فريق تنفيذ يعمل معكم لفترة أطول، أو مراجعة
                    قصيرة لنظام قائم بالفعل.</p>
                    <h2>مشاريع بنطاق محدد</h2>
                    <p>مناسبة عندما تكون المشكلة واضحة والموعد النهائي أهم من المرونة. نحدد السعر
                    والتاريخ، ويبقيان ثابتين ما لم يتغير النطاق كتابةً.</p>
                    <h2>فرق التنفيذ</h2>
                    <p>فريق دائم من ثلاثة إلى ستة أشخاص يعمل وفق خطتكم. معظم علاقاتنا طويلة الأمد تأخذ
                    هذا الشكل.</p>
                    <h2>المراجعات</h2>
                    <p>أسبوع أو أسبوعان في قراءة الشيفرة والحديث مع الفريق وتدوين ما وجدناه.</p>
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
                    <h2>Architecture reviews</h2>
                    <p>Two people spend a week with your codebase and your engineers, then write up what
                    is likely to break first and what it would cost to prevent. No slides.</p>
                    <h2>Hiring plans</h2>
                    <p>Working out what shape the team needs to be in twelve months, and in what order to
                    hire so that each person has someone to learn from when they arrive.</p>
                    <h2>Delivery audits</h2>
                    <p>For when projects keep slipping and nobody can say exactly why. Usually the answer
                    is in how work is estimated and handed over rather than in the engineering.</p>
                    <p>Engagements run from a single day workshop to a standing monthly review.</p>
                    HTML,
            ],
            [
                'menu' => 'news',
                'title' => 'Company Update',
                'title_ar' => 'تحديث الشركة',
                'slug' => 'company-update',
                'status' => PageStatus::Published,
                'published_at' => now()->subDays(5),
                'position' => 0,
                'body' => <<<'HTML'
                    <h2>Where things stand</h2>
                    <p>The first half of the year closed ahead of plan. Two new clients came on board and
                    the support team grew by four people.</p>
                    <h2>Delivery</h2>
                    <p>Eleven releases went out, two of which slipped by a week. Both slips came from the
                    same cause, a dependency on a third party sandbox that was down more than it was up,
                    and we have moved that work behind a stub for the rest of the year.</p>
                    <h2>The team</h2>
                    <p>Four people joined support and one moved across from delivery. We are still looking
                    for a second frontend engineer, which has been open longer than we would like.</p>
                    <h2>What is next</h2>
                    <p>The next update goes out at the end of the quarter, with the annual report following
                    in the new year.</p>
                    HTML,
                'body_ar' => <<<'HTML'
                    <h2>أين نقف الآن</h2>
                    <p>أُغلق النصف الأول من العام قبل الموعد المخطط له. انضم عميلان جديدان وكبر فريق
                    الدعم بأربعة أشخاص.</p>
                    <h2>التنفيذ</h2>
                    <p>صدرت إحدى عشرة نسخة، تأخرت اثنتان منها أسبوعًا واحدًا للسبب نفسه.</p>
                    <h2>الفريق</h2>
                    <p>انضم أربعة أشخاص إلى الدعم وانتقل واحد من فريق التنفيذ. ما زلنا نبحث عن مهندس
                    واجهات ثانٍ.</p>
                    <h2>ما هو قادم</h2>
                    <p>التحديث القادم في نهاية الربع، ويليه التقرير السنوي في العام الجديد.</p>
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
