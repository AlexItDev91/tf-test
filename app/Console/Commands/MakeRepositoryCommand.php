<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository
        {name? : Repository name (e.g. ProductRepository or Product)}
        {--model= : Optional model class name (e.g. Product)}
        {--no-cache : Do not generate Cached implementation}
        {--force : Overwrite existing files}
        {--tests : Generate repository tests (Pest or PHPUnit auto-detected)}';

    protected $description = 'Create repository Contract + Implementations (Eloquent + optional Cached)';

    public function handle(): int
    {
        $input = $this->resolveInput();

        return $this->generate(
            baseName: $input['baseName'],
            modelClass: $input['modelClass'],
            withCache: $input['withCache'],
            force: $input['force'],
            withTests: $input['withTests'],
        );
    }

    private function resolveInput(): array
    {
        $name = trim((string) ($this->argument('name') ?? ''));
        $hasName = $name !== '';

        $force = (bool) $this->option('force');
        $withCache = ! $this->option('no-cache');

        $model = $this->option('model');
        $model = is_string($model) ? trim($model) : '';

        $withTests = (bool) $this->option('tests');

        if (! $hasName) {
            $wiz = $this->wizard();

            // allow overriding from option (CLI wins)
            $wiz['force'] = $force ?: $wiz['force'];
            $wiz['withTests'] = $withTests ?: $wiz['withTests'];

            return $wiz;
        }

        $inputName = Str::studly($name);
        $baseName = $this->normalizeBaseName($inputName);

        return [
            'baseName' => $baseName,
            'modelClass' => $model !== '' ? Str::studly($model) : null,
            'withCache' => $withCache,
            'force' => $force,
            'withTests' => $withTests,
        ];
    }

    private function wizard(): array
    {
        $name = text(
            label: 'Repository name',
            placeholder: 'E.g. Product or ProductRepository',
            required: true
        );

        $inputName = Str::studly($name);
        $baseName = $this->normalizeBaseName($inputName);

        $model = text(
            label: 'Which model should this repository work with? (optional)',
            placeholder: 'E.g. Product (leave empty if not tied to a model)',
            hint: 'We do not create the model, we only type-hint it in the repository.'
        );

        $modelClass = $model !== '' ? Str::studly($model) : null;

        $withCache = confirm(
            label: 'Generate Cached implementation?',
            default: true
        );

        $withTests = confirm(
            label: 'Generate tests?',
            default: true
        );

        $paths = $this->paths($baseName);
        $force = (bool) $this->option('force');

        $testPaths = $this->testPaths($baseName, $modelClass, $withCache);

        $hasExisting = (
            File::exists($paths['contract']) ||
            File::exists($paths['eloquent']) ||
            ($withCache && File::exists($paths['cached'])) ||
            ($withTests && (
                File::exists($testPaths['eloquent_test']) ||
                ($withCache && File::exists($testPaths['cache_test']))
            ))
        );

        if (! $force && $hasExisting) {
            $force = confirm(
                label: 'Some files already exist. Overwrite?',
                default: false
            );
        }

        return [
            'baseName' => $baseName,
            'modelClass' => $modelClass,
            'withCache' => $withCache,
            'force' => $force,
            'withTests' => $withTests,
        ];
    }

    private function generate(
        string $baseName,
        ?string $modelClass,
        bool $withCache,
        bool $force,
        bool $withTests
    ): int {
        $repositoryClass = $baseName.'Repository';
        $contractClass = $baseName.'RepositoryContract';
        $cacheClass = $baseName.'CacheRepository';

        $paths = $this->paths($baseName);
        $testPaths = $this->testPaths($baseName, $modelClass, $withCache);

        $this->ensureDirectories($withCache, $withTests, $testPaths);

        $vars = [
            'base_name' => $baseName,
            'repository_class' => $repositoryClass,
            'contract_class' => $contractClass,
            'cache_class' => $cacheClass,

            'model' => $modelClass ?? '',
            'model_use' => $modelClass ? "use App\\Models\\{$modelClass};" : '',

            // cache key prefix: prefer model name, fallback to base
            'cache_prefix' => Str::snake($modelClass ?: $baseName),

            // test helpers
            'eloquent_test_class' => $baseName.'RepositoryTest',
            'cache_test_class' => $cacheClass.'Test',
        ];

        $created = 0;

        $created += $this->writeFile(
            $paths['contract'],
            $this->renderContractStub($vars, $modelClass),
            $force
        );

        $created += $this->writeFile(
            $paths['eloquent'],
            $this->renderEloquentStub($vars, $modelClass),
            $force
        );

        if ($withCache) {
            $created += $this->writeFile(
                $paths['cached'],
                $this->renderCachedStub($vars, $modelClass),
                $force
            );
        }

        $createdProvider = false;

        if ($created > 0) {
            $createdProvider = $this->ensureRepositoryServiceProviderExists();
            $this->appendBindingToRepositoryServiceProvider($baseName, $withCache);
        }

        if ($withTests) {
            $created += $this->writeFile(
                $testPaths['eloquent_test'],
                $this->renderEloquentTestStub($vars, $modelClass),
                $force
            );

            if ($withCache) {
                $created += $this->writeFile(
                    $testPaths['cache_test'],
                    $this->renderCacheTestStub($vars, $modelClass),
                    $force
                );
            }
        }

        if ($created === 0) {
            $this->info('Nothing to do (all files already exist). Use --force to overwrite.');

            return self::SUCCESS;
        }

        $this->info('Generated:');
        $this->line(' - '.$this->relative($paths['contract']));
        $this->line(' - '.$this->relative($paths['eloquent']));
        if ($withCache) {
            $this->line(' - '.$this->relative($paths['cached']));
        }

        if ($withTests) {
            $this->line(' - '.$this->relative($testPaths['eloquent_test']));
            if ($withCache) {
                $this->line(' - '.$this->relative($testPaths['cache_test']));
            }
        }

        if ($createdProvider) {
            $this->line(' - app/Providers/RepositoryServiceProvider.php');
        }

        return self::SUCCESS;
    }

    private function renderContractStub(array $vars, ?string $modelClass): string
    {
        return $this->stub(
            $modelClass ? 'repository/contract/model' : 'repository/contract/generic',
            $vars
        );
    }

    private function renderEloquentStub(array $vars, ?string $modelClass): string
    {
        return $this->stub(
            $modelClass ? 'repository/eloquent/model' : 'repository/eloquent/generic',
            $vars
        );
    }

    private function renderCachedStub(array $vars, ?string $modelClass): string
    {
        return $this->stub(
            $modelClass ? 'repository/cached/model' : 'repository/cached/generic',
            $vars
        );
    }

    private function renderEloquentTestStub(array $vars, ?string $modelClass): string
    {
        return $this->stub(
            $this->isPest()
                ? ($modelClass ? 'tests/pest/repository-eloquent-model' : 'tests/pest/repository-eloquent-generic')
                : ($modelClass ? 'tests/phpunit/repository-eloquent-model' : 'tests/phpunit/repository-eloquent-generic'),
            $vars
        );
    }

    private function renderCacheTestStub(array $vars, ?string $modelClass): string
    {
        return $this->stub(
            $this->isPest()
                ? ($modelClass ? 'tests/pest/repository-cache-model' : 'tests/pest/repository-cache-generic')
                : ($modelClass ? 'tests/phpunit/repository-cache-model' : 'tests/phpunit/repository-cache-generic'),
            $vars
        );
    }

    private function normalizeBaseName(string $inputName): string
    {
        $baseName = $inputName;

        foreach ([
            'RepositoryContract',
            'CacheRepository',
            'Repository',
        ] as $suffix) {
            if (Str::endsWith($baseName, $suffix)) {
                $baseName = Str::beforeLast($baseName, $suffix);
                break;
            }
        }

        return $baseName;
    }

    private function paths(string $baseName): array
    {
        $repositoryClass = $baseName.'Repository';
        $contractClass = $baseName.'RepositoryContract';
        $cacheClass = $baseName.'CacheRepository';

        return [
            'contract' => app_path("Repositories/Contracts/{$contractClass}.php"),
            'eloquent' => app_path("Repositories/Implementations/Eloquent/{$repositoryClass}.php"),
            'cached' => app_path("Repositories/Implementations/Cached/{$cacheClass}.php"),
        ];
    }

    private function testPaths(string $baseName, ?string $modelClass, bool $withCache): array
    {
        // keep tests stable regardless of Pest/PHPUnit (folder differs per stub contents)
        // Pest: tests/Feature/Repositories/* + tests/Unit/Repositories/*
        // PHPUnit: same paths, but class-based tests.
        $eloquent = base_path("tests/Feature/Repositories/{$baseName}RepositoryTest.php");
        $cache = base_path("tests/Unit/Repositories/{$baseName}CacheRepositoryTest.php");

        // Use cache class name in filename to be clearer:
        // if model-less, still named after baseName
        $cacheFile = base_path("tests/Unit/Repositories/{$baseName}CacheRepositoryTest.php");

        return [
            'eloquent_test' => $eloquent,
            'cache_test' => $cacheFile,
        ];
    }

    private function ensureDirectories(bool $withCache, bool $withTests, array $testPaths): void
    {
        $dirs = [
            app_path('Repositories/Contracts'),
            app_path('Repositories/Implementations/Eloquent'),
        ];

        if ($withCache) {
            $dirs[] = app_path('Repositories/Implementations/Cached');
        }

        if ($withTests) {
            $dirs[] = base_path('tests/Feature/Repositories');
            $dirs[] = base_path('tests/Unit/Repositories');
        }

        foreach ($dirs as $dir) {
            if (! File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
        }
    }

    private function writeFile(string $path, string $content, bool $force): int
    {
        if (File::exists($path) && ! $force) {
            $this->warn('Skip (exists): '.$this->relative($path));

            return 0;
        }

        File::put($path, $content);

        return 1;
    }

    private function relative(string $absolutePath): string
    {
        $base = base_path();

        return ltrim(Str::replaceFirst($base, '', $absolutePath), DIRECTORY_SEPARATOR);
    }

    private function ensureRepositoryServiceProviderExists(): bool
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (File::exists($providerPath)) {
            return false;
        }

        File::ensureDirectoryExists(app_path('Providers'));

        File::put(
            $providerPath,
            $this->stub('provider/repository-provider')
        );

        $this->ensureProviderRegistered();

        return true;
    }

    private function ensureProviderRegistered(): void
    {
        $providersFile = base_path('bootstrap/providers.php');

        if (! File::exists($providersFile)) {
            return;
        }

        $content = File::get($providersFile);

        if (str_contains($content, 'App\\Providers\\RepositoryServiceProvider::class')) {
            return;
        }

        $content = preg_replace(
            '/\];\s*$/',
            "    App\\Providers\\RepositoryServiceProvider::class,\n];",
            $content
        );

        File::put($providersFile, $content);
    }

    private function appendBindingToRepositoryServiceProvider(string $baseName, bool $withCache): void
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (! File::exists($providerPath)) {
            return;
        }

        $content = File::get($providerPath);

        $contractClass = $baseName.'RepositoryContract';
        $repositoryClass = $baseName.'Repository';
        $cacheClass = $baseName.'CacheRepository';

        $contractFqn = "App\\Repositories\\Contracts\\{$contractClass}";
        $eloquentFqn = "App\\Repositories\\Implementations\\Eloquent\\{$repositoryClass}";
        $cachedFqn = "App\\Repositories\\Implementations\\Cached\\{$cacheClass}";

        if (str_contains($content, $contractFqn) || str_contains($content, "{$contractClass}::class")) {
            return;
        }

        $uses = [
            "use {$contractFqn};",
            "use {$eloquentFqn};",
        ];

        if ($withCache) {
            $uses[] = "use {$cachedFqn};";
        }

        $content = $this->ensureUses($content, $uses);

        $binding = $this->stub(
            $withCache ? 'provider/binding-cached' : 'provider/binding-plain',
            [
                'contract' => $contractClass,
                'repository' => $repositoryClass,
                'cache' => $cacheClass,
            ]
        );

        $content = $this->insertIntoRegisterMethod($content, $binding);

        File::put($providerPath, $content);

        $this->info("Added binding to RepositoryServiceProvider ({$baseName})");
    }

    private function ensureUses(string $content, array $useLines): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        foreach ($useLines as $line) {
            if (! str_contains($content, $line)) {
                $content = $this->addUseLine($content, $line);
            }
        }

        return $content;
    }

    private function addUseLine(string $content, string $useLine): string
    {
        $lines = explode("\n", $content);

        $lastUseIndex = null;
        $namespaceIndex = null;

        foreach ($lines as $i => $line) {
            $trim = trim($line);

            if ($namespaceIndex === null && preg_match('/^namespace\s+[^;]+;$/', $trim)) {
                $namespaceIndex = $i;
            }

            if (preg_match('/^use\s+[^;]+;$/', $trim)) {
                $lastUseIndex = $i;
            }
        }

        if ($lastUseIndex !== null) {
            array_splice($lines, $lastUseIndex + 1, 0, [$useLine]);

            return implode("\n", $lines);
        }

        if ($namespaceIndex !== null) {
            $insertAt = $namespaceIndex + 1;

            while (isset($lines[$insertAt]) && trim($lines[$insertAt]) === '') {
                $insertAt++;
            }

            array_splice($lines, $insertAt, 0, ['', $useLine]);

            return implode("\n", $lines);
        }

        return $useLine."\n".$content;
    }

    private function insertIntoRegisterMethod(string $content, string $bindingBlock): string
    {
        $pattern1 = '/public function register\(\): void\s*\{\s*\n\s*\/\/\s*\n/s';

        if (preg_match($pattern1, $content)) {
            return preg_replace_callback(
                $pattern1,
                fn () => "public function register(): void\n    {\n".$bindingBlock,
                $content,
                1
            );
        }

        $pattern2 = '/(public function register\(\): void\s*\{\s*\n)/s';

        return preg_replace_callback(
            $pattern2,
            fn ($m) => $m[1].$bindingBlock,
            $content,
            1
        );
    }

    private function stub(string $name, array $vars = []): string
    {
        $path = $this->stubsPath().'/'.$name.'.stub';

        if (! File::exists($path)) {
            throw new \RuntimeException("Stub not found: {$path}");
        }

        $content = File::get($path);

        foreach ($vars as $key => $value) {
            $content = str_replace('{{ '.$key.' }}', (string) $value, $content);
        }

        return $content;
    }

    private function stubsPath(): string
    {
        return __DIR__.'/stubs';
    }

    private function isPest(): bool
    {
        return File::exists(base_path('tests/Pest.php'));
    }
}
