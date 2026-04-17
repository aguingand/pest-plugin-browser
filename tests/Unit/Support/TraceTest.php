<?php

declare(strict_types=1);

use Pest\Browser\Support\Trace;

it('places traces under tests/Browser/Traces', function (): void {
    expect(Trace::dir())
        ->toEndWith('/tests/Browser/Traces');
});

it('returns the full path for a trace file with .zip extension', function (): void {
    expect(Trace::path('my-trace.zip'))
        ->toEndWith('/tests/Browser/Traces/my-trace.zip');
});

it('appends .zip extension when no extension is provided', function (): void {
    expect(Trace::path('my-trace'))
        ->toEndWith('/tests/Browser/Traces/my-trace.zip');
});

it('strips leading slash from filename', function (): void {
    expect(Trace::path('/my-trace'))
        ->toEndWith('/tests/Browser/Traces/my-trace.zip');
});

it('creates the traces directory if it does not exist', function (): void {
    $dir = Trace::dir();

    if (is_dir($dir)) {
        rmdir($dir);
    }

    Trace::ensureDirectoryExists();

    expect(is_dir($dir))->toBeTrue();
});

it('deletes a trace file', function (): void {
    Trace::ensureDirectoryExists();

    $path = Trace::path('delete-me');
    file_put_contents($path, 'test');

    expect(file_exists($path))->toBeTrue();

    Trace::delete('delete-me');

    expect(file_exists($path))->toBeFalse();
});
