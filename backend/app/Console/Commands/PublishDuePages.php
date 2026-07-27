<?php

namespace App\Console\Commands;

use App\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PublishDuePages extends Command
{
    protected $signature = 'pages:publish-due';

    protected $description = 'Promote pages whose publish date has arrived and withdraw any that no longer qualify';

    public function handle(): int
    {
        $published = Page::query()
            ->where('is_live', false)
            ->where('status', PageStatus::Published)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->update($this->flag(true));

        // The reverse case matters just as much: a page pulled back to draft, or
        // given a date further out, has to leave the public site again.
        $withdrawn = Page::query()
            ->where('is_live', true)
            ->where(function (Builder $query): void {
                $query->where('status', '!=', PageStatus::Published)
                    ->orWhere('published_at', '>', now());
            })
            ->update($this->flag(false));

        $this->info("Published {$published}, withdrew {$withdrawn}.");

        return self::SUCCESS;
    }

    /**
     * Going live is a scheduled transition rather than somebody editing the
     * page, so updated_at is written back to itself and the audit trail keeps
     * pointing at the last real editor.
     *
     * @return array<string, mixed>
     */
    private function flag(bool $live): array
    {
        return ['is_live' => $live, 'updated_at' => DB::raw('updated_at')];
    }
}
