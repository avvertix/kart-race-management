<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every `__('...')` string in app/ and resources/views/ has an Italian translation in lang/it.json. Strings missing
 * before this check existed are listed in tests/Unit/italian-translations-baseline.txt; remove a line once translated.
 */
class ItalianTranslationsTest extends TestCase
{
    /**
     * The single-quoted strings passed to __() in the PHP and Blade files, apart from group keys like `licences.status`.
     *
     * @return array<int, string>
     */
    public static function stringsUsedIn(string $root): array
    {
        $strings = [];

        foreach (['app', 'resources/views'] as $folder) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$folder}", RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                preg_match_all("/__\(\s*'((?:[^'\\\\]|\\\\.)*)'/", (string) file_get_contents($file->getPathname()), $matches);

                foreach ($matches[1] as $string) {
                    $strings[] = stripslashes($string);
                }
            }
        }

        return array_values(array_unique(array_filter($strings, fn (string $string) => preg_match('/^[a-z0-9_-]+\.[a-zA-Z0-9_.-]+$/', $string) !== 1)));
    }

    public function test_every_new_string_has_an_italian_translation(): void
    {
        $root = dirname(__DIR__, 2);
        $translations = json_decode((string) file_get_contents("{$root}/lang/it.json"), true, flags: JSON_THROW_ON_ERROR);
        $baseline = array_filter(array_map(fn (string $line) => mb_rtrim($line, "\r\n"), file("{$root}/tests/Unit/italian-translations-baseline.txt") ?: []));

        $missing = array_values(array_diff(self::stringsUsedIn($root), array_keys($translations), $baseline));

        $this->assertSame([], $missing, 'Add these strings to lang/it.json.');
    }
}
