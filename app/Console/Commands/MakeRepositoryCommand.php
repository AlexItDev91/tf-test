<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:app-repository
         {name? : Repository name (e.g. ProductRepository or Product)}
        {--model= : Optional model class name (e.g. Product)}
        {--no-cache : Do not generate Cached implementation}
        {--force : Overwrite existing files}';

    protected $description = 'Create repository Contract + Implementations (Eloquent + optional Cached)';

    public function handle(): int
    {
        $input = $this->resolveInput();

        return $this->generate(
            baseName: $input['baseName'],
            modelClass: $input['modelClass'],
            withCache: $input['withCache'],
            force: $input['force'],
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

        if (! $hasName) {
            return $this->wizard();
        }

        $inputName = Str::studly($name);
        $baseName = $this->normalizeBaseName($inputName);

        return [
            'baseName' => $baseName,
            'modelClass' => $model !== '' ? Str::studly($model) : null,
            'withCache' => $withCache,
            'force' => $force,
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
            label: 'Which model should this repository use?',
            placeholder: 'E.g. Product or leave empty',
            required: false,
            hint: 'Leave empty if repository is not tied to a model'
        );

        $modelClass = $model !== '' ? Str::studly($model) : null;

        $withCache = confirm(
            label: 'Generate Cached implementation?',
            default: true
        );

        // проверка существующих файлов
        $paths = $this->paths($baseName);
        $force = (bool) $this->option('force');

        if (! $force && (
            File::exists($paths['contract']) ||
            File::exists($paths['eloquent']) ||
            ($withCache && File::exists($paths['cached']))
        )) {
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
        ];
    }

    private function generate(string $baseName, ?string $modelClass, bool $withCache, bool $force): int
    {
        $repositoryClass = $baseName.'Repository';
        $contractClass = $baseName.'RepositoryContract';
        $cacheClass = $baseName.'CacheRepository';

        $paths = $this->paths($baseName);

        $this->ensureDirectories($withCache);

        $created = 0;

        $created += $this->writeFile(
            $paths['contract'],
            $this->buildContract($contractClass, $modelClass),
            $force
        );

        $created += $this->writeFile(
            $paths['eloquent'],
            $this->buildEloquentImplementation($repositoryClass, $contractClass, $modelClass),
            $force
        );

        if ($withCache) {
            $created += $this->writeFile(
                $paths['cached'],
                $this->buildCachedImplementation($cacheClass, $contractClass, $modelClass),
                $force
            );
        }

        if ($created === 0) {
            $this->info('Nothing to do (all files already exist). Use --force to overwrite.');
        } else {
            $this->info('Repository generated:');
            $this->line(' - '.$this->relative($paths['contract']));
            $this->line(' - '.$this->relative($paths['eloquent']));
            if ($withCache) {
                $this->line(' - '.$this->relative($paths['cached']));
            }
        }

        $this->ensureRepositoryServiceProviderExists();
        $this->appendBindingToRepositoryServiceProvider($baseName, $withCache);

        return self::SUCCESS;
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

    private function ensureDirectories(bool $withCache): void
    {
        $dirs = [
            app_path('Repositories/Contracts'),
            app_path('Repositories/Implementations/Eloquent'),
        ];

        if ($withCache) {
            $dirs[] = app_path('Repositories/Implementations/Cached');
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
        $app = base_path();

        return ltrim(Str::replaceFirst($app, '', $absolutePath), DIRECTORY_SEPARATOR);
    }

    private function buildContract(string $contractClass, ?string $modelClass): string
    {
        $modelUse = $modelClass ? "use App\\Models\\{$modelClass};\n" : '';
        $methods = $this->contractMethods($modelClass);

        return <<<PHP
<?php

namespace App\Repositories\Contracts;

{$modelUse}interface {$contractClass}
{
{$methods}}
PHP;
    }

    private function buildEloquentImplementation(string $repositoryClass, string $contractClass, ?string $modelClass): string
    {
        $modelUse = $modelClass ? "use App\\Models\\{$modelClass};\n" : '';
        $imports = "use App\\Repositories\\Contracts\\{$contractClass};\n{$modelUse}";
        $body = $this->eloquentMethods($modelClass);

        return <<<PHP
<?php

namespace App\Repositories\Implementations\Eloquent;

{$imports}class {$repositoryClass} implements {$contractClass}
{
{$body}}
PHP;
    }

    private function buildCachedImplementation(string $cacheClass, string $contractClass, ?string $modelClass): string
    {
        $modelUse = $modelClass ? "use App\\Models\\{$modelClass};\n" : '';
        $imports = "use App\\Repositories\\Contracts\\{$contractClass};\nuse Illuminate\\Support\\Facades\\Cache;\n{$modelUse}";
        $body = $this->cachedMethods($modelClass);

        return <<<PHP
<?php

namespace App\Repositories\Implementations\Cached;

{$imports}class {$cacheClass} implements {$contractClass}
{
    public function __construct(
        private readonly {$contractClass} \$inner
    ) {}

{$body}}
PHP;
    }

    private function contractMethods(?string $modelClass): string
    {
        if ($modelClass) {
            $model = $modelClass;

            return
                "    public function findOrFail(int \$id): {$model};

    public function findById(int \$id): ?{$model};

    public function create(array \$data): {$model};

    public function update(int \$id, array \$data): {$model};

    public function delete(int \$id): void;
";
        }

        return "    // Define repository methods here.\n";
    }

    private function eloquentMethods(?string $modelClass): string
    {
        if (! $modelClass) {
            return "    // Eloquent/DB implementation here.\n";
        }

        $model = $modelClass;

        return
            "    public function findOrFail(int \$id): {$model}
    {
        return {$model}::query()->findOrFail(\$id);
    }

    public function findById(int \$id): ?{$model}
    {
        return {$model}::query()->find(\$id);
    }

    public function create(array \$data): {$model}
    {
        return {$model}::query()->create(\$data);
    }

    public function update(int \$id, array \$data): {$model}
    {
        \$model = {$model}::query()->findOrFail(\$id);
        \$model->fill(\$data);
        \$model->save();

        return \$model;
    }

    public function delete(int \$id): void
    {
        {$model}::query()->whereKey(\$id)->delete();
    }
";
    }

    private function cachedMethods(?string $modelClass): string
    {
        if (! $modelClass) {
            return "    // Cached decorator implementation here.\n";
        }

        $model = $modelClass;
        $keyPrefix = Str::snake($model);

        return
            "    public function findOrFail(int \$id): {$model}
    {
        return \$this->inner->findOrFail(\$id);
    }

    public function findById(int \$id): ?{$model}
    {
        return Cache::remember(
            \$this->keyById(\$id),
            now()->addMinutes(10),
            fn () => \$this->inner->findById(\$id)
        );
    }

    public function create(array \$data): {$model}
    {
        \$model = \$this->inner->create(\$data);

        Cache::forget(\$this->keyById((int)\$model->getKey()));

        return \$model;
    }

    public function update(int \$id, array \$data): {$model}
    {
        \$model = \$this->inner->update(\$id, \$data);

        Cache::forget(\$this->keyById(\$id));

        return \$model;
    }

    public function delete(int \$id): void
    {
        \$this->inner->delete(\$id);

        Cache::forget(\$this->keyById(\$id));
    }

    private function keyById(int \$id): string
    {
        return \"{$keyPrefix}:id:{\$id}\";
    }
";
    }

    private function ensureRepositoryServiceProviderExists(): void
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (! File::exists($providerPath)) {
            File::ensureDirectoryExists(app_path('Providers'));
            File::put($providerPath, $this->repositoryServiceProviderStub());
            $this->info('Created RepositoryServiceProvider');
        } else {
            $this->info('RepositoryServiceProvider already exists');
        }

        $this->ensureProviderRegistered();
    }

    private function ensureProviderRegistered(): void
    {
        $providersFile = base_path('bootstrap/providers.php');

        if (! File::exists($providersFile)) {
            return;
        }

        $content = File::get($providersFile);

        if (str_contains($content, 'App\\Providers\\RepositoryServiceProvider::class')) {
            $this->info('...and registered in bootstrap/providers.php');

            return;
        }

        $content = preg_replace(
            '/\];\s*$/',
            "    App\\Providers\\RepositoryServiceProvider::class,\n];",
            $content
        );

        File::put($providersFile, $content);

        $this->info('Registered RepositoryServiceProvider in bootstrap/providers.php');
    }

    private function repositoryServiceProviderStub(): string
    {
        return <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
}
PHP;
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

        $binding = $withCache
            ? <<<PHP
        \$this->app->bind({$contractClass}::class, function (\$app) {
            return new {$cacheClass}(
                \$app->make({$repositoryClass}::class)
            );
        });

PHP
            : <<<PHP
        \$this->app->bind({$contractClass}::class, {$repositoryClass}::class);

PHP;
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
                function () use ($bindingBlock) {
                    return "public function register(): void\n    {\n".$bindingBlock;
                },
                $content,
                1
            );
        }

        $pattern2 = '/(public function register\(\): void\s*\{\s*\n)/s';

        return preg_replace_callback(
            $pattern2,
            function ($m) use ($bindingBlock) {
                return $m[1].$bindingBlock;
            },
            $content,
            1
        );
    }
}
