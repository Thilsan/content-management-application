<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (! function_exists('unique_slug')) {
    /**
     * Slugify a title and keep appending a counter until the slug is free.
     *
     * Soft deleted rows still hold their slug in the unique index, so they are
     * deliberately included in the lookup. Pass the id of the row being updated
     * to let it keep the slug it already owns.
     */
    function unique_slug(string $value, string $table, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $counter = 2;

        while (
            DB::table($table)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereNot('id', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

if (! function_exists('html_excerpt')) {
    /**
     * Turn CKEditor markup into a short plain text summary for list views.
     */
    function html_excerpt(?string $html, int $limit = 160): string
    {
        $markup = (string) $html;

        $flatten = static function (string $value): string {
            $text = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5);

            // CKEditor emits plenty of &nbsp;, which decodes to U+00A0. That is
            // not matched by \s, so collapse it alongside ordinary whitespace.
            return trim(preg_replace('/[\s\x{00a0}]+/u', ' ', $text) ?? '');
        };

        // A heading usually restates the title, which reads badly when it runs
        // into the first sentence. Start the excerpt at the prose instead, and
        // fall back to the whole thing when the body is nothing but headings.
        $body = preg_replace('#<(h[1-6])\b[^>]*>.*?</\1>#is', ' ', $markup) ?? $markup;

        $text = $flatten($body) ?: $flatten($markup);

        return Str::limit($text, $limit);
    }
}
