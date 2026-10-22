<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Turns plain page text into safe HTML. Everything is escaped FIRST, so no tag a
 * writer types can ever reach the page. Only these markers are understood:
 *
 *   ## Heading        (large heading)
 *   ### Sub heading   (small heading)
 *   - item            (bullet list; one item per line)
 *   **bold**          (inside a line)
 *
 * A blank line ends a paragraph or a list. Links, images and HTML are not supported on purpose.
 */
final class SafeText
{
    public static function toHtml(string $text): HtmlString
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));

        $html = '';
        $paragraph = [];
        $items = [];

        $flush = function () use (&$html, &$paragraph, &$items): void {
            if ($paragraph !== []) {
                $html .= '<p class="mt-3 leading-relaxed text-stone-700">'.implode('<br>', $paragraph).'</p>';
                $paragraph = [];
            }

            if ($items !== []) {
                $html .= '<ul class="mt-3 list-disc space-y-1 ps-6 leading-relaxed text-stone-700">'
                    .implode('', array_map(fn (string $item): string => '<li>'.$item.'</li>', $items))
                    .'</ul>';
                $items = [];
            }
        };

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                $flush();

                continue;
            }

            if (str_starts_with($line, '### ')) {
                $flush();
                $html .= '<h3 class="mt-6 text-lg font-semibold text-stone-900">'.self::inline(substr($line, 4)).'</h3>';

                continue;
            }

            if (str_starts_with($line, '## ')) {
                $flush();
                $html .= '<h2 class="mt-8 text-xl font-semibold text-brand-800">'.self::inline(substr($line, 3)).'</h2>';

                continue;
            }

            if (str_starts_with($line, '- ')) {
                if ($paragraph !== []) {
                    $flush();
                }

                $items[] = self::inline(substr($line, 2));

                continue;
            }

            if ($items !== []) {
                $flush();
            }

            $paragraph[] = self::inline($line);
        }

        $flush();

        return new HtmlString($html);
    }

    /** Escape, then allow **bold**. The escaped text can not hold a tag, so the result is safe. */
    private static function inline(string $text): string
    {
        $escaped = e(trim($text));

        return preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped) ?? $escaped;
    }
}
