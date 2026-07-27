<?php

it('strips markup and collapses whitespace into an excerpt', function (): void {
    $html = "<h2>Title</h2>\n<p>Some <strong>bold</strong> copy&nbsp;here.</p>";

    expect(html_excerpt($html, 20))->toBe('Title Some bold copy...');
});

it('returns the whole text when it is shorter than the limit', function (): void {
    expect(html_excerpt('<p>Short.</p>', 100))->toBe('Short.');
});

it('handles an empty body', function (): void {
    expect(html_excerpt(null))->toBe('')
        ->and(html_excerpt(''))->toBe('');
});

it('drops the markup of an empty paragraph', function (): void {
    expect(html_excerpt('<p>&nbsp;</p>'))->toBe('');
});
