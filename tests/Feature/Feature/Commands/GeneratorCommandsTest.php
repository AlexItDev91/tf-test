<?php

namespace Tests\Feature\Commands;

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->servicesPath = app_path('Services');
    $this->repositoriesPath = app_path('Repositories');
});

afterEach(function () {
    // Clean up created files
    if (File::exists($this->servicesPath.'/TestService.php')) {
        File::delete($this->servicesPath.'/TestService.php');
    }

    $repoBase = 'Test';
    $files = [
        app_path("Repositories/Contracts/{$repoBase}RepositoryContract.php"),
        app_path("Repositories/Implementations/Eloquent/{$repoBase}Repository.php"),
        app_path("Repositories/Implementations/Cached/{$repoBase}CacheRepository.php"),
    ];

    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }
});

it('creates a service class', function () {
    $name = 'Test';
    $expectedPath = app_path("Services/{$name}Service.php");

    if (File::exists($expectedPath)) {
        File::delete($expectedPath);
    }

    $this->artisan('make:service', ['name' => $name])
        ->expectsOutput("Service created: app/Services/{$name}Service.php")
        ->assertSuccessful();

    expect(File::exists($expectedPath))->toBeTrue();
    expect(File::get($expectedPath))->toContain('class TestService');
});

it('creates a repository with contract and implementations', function () {
    $name = 'Test';

    $this->artisan('make:app-repository', [
        'name' => $name,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(File::exists(app_path("Repositories/Contracts/{$name}RepositoryContract.php")))->toBeTrue();
    expect(File::exists(app_path("Repositories/Implementations/Eloquent/{$name}Repository.php")))->toBeTrue();
    expect(File::exists(app_path("Repositories/Implementations/Cached/{$name}CacheRepository.php")))->toBeTrue();
});
