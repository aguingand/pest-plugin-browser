<?php

declare(strict_types=1);

namespace Pest\Browser\Support;

use Pest\TestSuite;

/**
 * @internal
 */
final class Trace
{
    /**
     * Return the path to the traces' directory.
     */
    public static function dir(): string
    {
        return TestSuite::getInstance()->rootPath
            .'/tests/Browser/Traces';
    }

    /**
     * Return the full path for a trace file.
     */
    public static function path(string $filename): string
    {
        $filename = self::dir().'/'.mb_ltrim($filename, '/');

        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.zip';
        }

        return $filename;
    }

    /**
     * Return the trace filename for the current test.
     */
    public static function filename(): string
    {
        // @phpstan-ignore-next-line
        return str_replace('__pest_evaluable_', '', test()->name());
    }

    /**
     * Ensure the traces directory exists.
     */
    public static function ensureDirectoryExists(): void
    {
        if (is_dir(self::dir()) === false) {
            @mkdir(self::dir(), 0755, true);
        }
    }

    /**
     * Delete a trace file.
     */
    public static function delete(string $filename): void
    {
        $path = self::path($filename);

        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
