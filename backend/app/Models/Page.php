<?php

namespace App\Models;

use App\Enums\PageStatus;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'menu_id',
        'title',
        'title_ar',
        'slug',
        'body',
        'body_ar',
        'cover_image',
        'status',
        'published_at',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'published_at' => 'datetime',
            'position' => 'integer',
            'is_live' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->created_by ??= auth()->id();
            $page->updated_by ??= auth()->id();
        });

        static::updating(function (self $page): void {
            $page->updated_by = auth()->id() ?? $page->updated_by;
        });

        // Settle the flag whenever an editor saves, so publishing something with
        // no date is live immediately rather than waiting for the next tick of
        // the scheduler. Dated pages are left to pages:publish-due.
        static::saving(function (self $page): void {
            $page->is_live = $page->qualifiesAsLive();
        });
    }

    /**
     * Published, and either undated or dated in the past.
     */
    public function qualifiesAsLive(): bool
    {
        return $this->status === PageStatus::Published
            && (is_null($this->published_at) || $this->published_at->isPast());
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Pages the public site is allowed to show. The publish date is not
     * evaluated here: the pages:publish-due command owns that transition and
     * records the answer in is_live.
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_live', true);
    }

    public function isVisible(): bool
    {
        return (bool) $this->is_live;
    }

    /**
     * Arabic when it exists, English otherwise. A half translated page still
     * reads, rather than showing an empty title next to Arabic prose.
     */
    public function titleIn(string $locale): string
    {
        return $locale === 'ar' && filled($this->title_ar) ? $this->title_ar : $this->title;
    }

    public function bodyIn(string $locale): string
    {
        return $locale === 'ar' && filled($this->body_ar) ? $this->body_ar : $this->body;
    }

    /** Whether this page can actually be read in the given language. */
    public function hasTranslation(string $locale): bool
    {
        return $locale === 'ar'
            ? filled($this->title_ar) && filled($this->body_ar)
            : true;
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image
            ? Storage::disk('public')->url($this->cover_image)
            : null;
    }
}
