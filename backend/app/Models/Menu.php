<?php

namespace App\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'position',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Menu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Build the nested menu tree from a single query and hydrate the
     * children relation on each node, so resources can recurse without
     * triggering a query per level.
     *
     * @param  Builder<Menu>|null  $query
     * @return Collection<int, Menu>
     */
    public static function tree(?Builder $query = null): Collection
    {
        $menus = ($query ?? static::query())
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $byParent = [];

        foreach ($menus as $menu) {
            $byParent[$menu->parent_id ?? 0][] = $menu;
        }

        foreach ($menus as $menu) {
            $menu->setRelation('children', collect($byParent[$menu->id] ?? []));
        }

        return collect($byParent[0] ?? []);
    }

    /**
     * Every id in this menu's subtree, including its own.
     *
     * @return list<int>
     */
    public function subtreeIds(): array
    {
        $ids = [$this->id];
        $frontier = [$this->id];

        while ($frontier !== []) {
            $frontier = static::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }
}
