<?php

it('strips markup and collapses whitespace into an excerpt', function (): void {
    $html = "<p>Some <strong>bold</strong>\n copy&nbsp;here.</p>";

    expect(html_excerpt($html, 20))->toBe('Some bold copy here.');
});

it('starts the excerpt at the prose rather than the heading', function (): void {
    $html = "<h2>A short history</h2>\n<p>We started in 2014 with three people.</p>";

    expect(html_excerpt($html, 30))->toBe('We started in 2014 with three...');
});

it('falls back to the heading when the body has nothing else', function (): void {
    expect(html_excerpt('<h2>Just a heading</h2>'))->toBe('Just a heading');
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
