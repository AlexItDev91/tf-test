<?php

namespace Tests\Feature\Command;

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

function serviceTestBase(): string
{
    return 'TestPest';
}

function servicePaths(): array
{
    $base = serviceTestBase();

    return [
        'service' => app_path("Services/{$base}Service.php"),
        'existing' => app_path("Services/{$base}ExistingService.php"),
        'forced' => app_path("Services/{$base}ForcedService.php"),
    ];
}

beforeEach(function () {
    foreach (servicePaths() as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

afterEach(function () {
    foreach (servicePaths() as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

it('creates a service class successfully', function () {
    $base = serviceTestBase();

    artisan("make:service {$base}Service")
        ->assertExitCode(0)
        ->expectsOutput("Service created: app/Services/{$base}Service.php");

    $path = app_path("Services/{$base}Service.php");
    expect($path)->toBeFile();

    $content = File::get($path);

    expect($content)->toContain('namespace App\Services;')
        ->and($content)->toContain("class {$base}Service")
        ->and($content)->toContain("// implement {$base} business logic here");
});

it('appends Service suffix if not provided', function () {
    $base = serviceTestBase();

    artisan("make:service {$base}")
        ->assertExitCode(0)
        ->expectsOutput("Service created: app/Services/{$base}Service.php");

    $path = app_path("Services/{$base}Service.php");
    expect($path)->toBeFile();

    $content = File::get($path);

    expect($content)->toContain("class {$base}Service")
        ->and($content)->toContain("// implement {$base} business logic here");
});

it('does not overwrite existing service without force option', function () {
    $base = serviceTestBase();
    $existing = app_path("Services/{$base}ExistingService.php");

    File::ensureDirectoryExists(app_path('Services'));
    File::put($existing, 'original content');

    artisan("make:service {$base}Existing")
        ->assertExitCode(0)
        ->expectsOutput("Skip (exists): app/Services/{$base}ExistingService.php");

    expect(File::get($existing))->toBe('original content');
});

it('overwrites existing service with force option', function () {
    $base = serviceTestBase();
    $forced = app_path("Services/{$base}ForcedService.php");

    File::ensureDirectoryExists(app_path('Services'));
    File::put($forced, 'original content');

    artisan("make:service {$base}Forced --force")
        ->assertExitCode(0)
        ->expectsOutput("Service created: app/Services/{$base}ForcedService.php");

    $content = File::get($forced);

    expect($content)->not->toBe('original content')
        ->and($content)->toContain("class {$base}ForcedService")
        ->and($content)->toContain("// implement {$base}Forced business logic here");
});
