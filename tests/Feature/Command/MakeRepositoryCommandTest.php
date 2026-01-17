<?php

namespace Tests\Feature\Command;

use Illuminate\Support\Facades\File;
use function Pest\Laravel\artisan;

beforeEach(function () {
    backupRepositoryServiceProvider();
    cleanupRepositoryFiles('TestPest');
});

afterEach(function () {
    cleanupRepositoryFiles('TestPest');
    restoreRepositoryServiceProvider();
});

it('generates contract eloquent and cached repositories and binds provider', function () {
    artisan('make:app-repository TestPest')
        ->assertExitCode(0);

    expect(app_path('Repositories/Contracts/TestPestRepositoryContract.php'))->toBeFile()
        ->and(app_path('Repositories/Implementations/Eloquent/TestPestRepository.php'))->toBeFile()
        ->and(app_path('Repositories/Implementations/Cached/TestPestCacheRepository.php'))->toBeFile();

    $providerPath = app_path('Providers/RepositoryServiceProvider.php');
    expect($providerPath)->toBeFile();

    $provider = File::get($providerPath);

    expect($provider)->toContain('use App\\Repositories\\Contracts\\TestPestRepositoryContract;')
        ->and($provider)->toContain('use App\\Repositories\\Implementations\\Eloquent\\TestPestRepository;')
        ->and($provider)->toContain('use App\\Repositories\\Implementations\\Cached\\TestPestCacheRepository;')
        ->and($provider)->toContain('TestPestRepositoryContract::class')
        ->and($provider)->toContain('new TestPestCacheRepository')
        ->and($provider)->toContain('$app->make(TestPestRepository::class)');

    $bootstrapProviders = base_path('bootstrap/providers.php');
    expect(File::get($bootstrapProviders))
        ->toContain('App\\Providers\\RepositoryServiceProvider::class');
});

it('generates repository without cache when --no-cache is used', function () {
    $this->artisan('make:app-repository TestPest --no-cache')
        ->assertExitCode(0);

    expect(app_path('Repositories/Implementations/Cached/TestPestCacheRepository.php'))->not->toBeFile();

    $provider = \Illuminate\Support\Facades\File::get(app_path('Providers/RepositoryServiceProvider.php'));

    expect($provider)->not->toContain('use App\\Repositories\\Implementations\\Cached\\TestPestCacheRepository;')
        ->and($provider)->not->toContain('new TestPestCacheRepository')
        ->and($provider)->toContain('TestPestRepositoryContract::class')
        ->and($provider)->toContain('TestPestRepository::class');
});

it('does not duplicate bindings or use statements on second run', function () {
    $this->artisan('make:app-repository', ['name' => 'TestPest'])->assertExitCode(0);
    $this->artisan('make:app-repository', ['name' => 'TestPest'])->assertExitCode(0);

    $provider = File::get(app_path('Providers/RepositoryServiceProvider.php'));

    expect(substr_count($provider, 'use App\\Repositories\\Contracts\\TestPestRepositoryContract;'))->toBe(1)
        ->and(substr_count($provider, 'use App\\Repositories\\Implementations\\Eloquent\\TestPestRepository;'))->toBe(1)
        ->and(substr_count($provider, 'use App\\Repositories\\Implementations\\Cached\\TestPestCacheRepository;'))->toBe(1)
        ->and(substr_count($provider, 'TestPestRepositoryContract::class'))->toBe(1);
});
