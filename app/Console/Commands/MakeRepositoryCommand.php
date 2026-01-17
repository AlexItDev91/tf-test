<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeRepositoryCommand extends Command
{
    protected $signature = 'make:repository
        {name : Repository name (e.g. ProductRepository or Product)}
        {--model= : Optional model class name (e.g. Product)}
        {--no-cache : Do not generate Cached implementation}
        {--force : Overwrite existing files}';

    protected $description = 'Create repository Contract + Implementations (Eloquent + optional Cached)';

    public function handle(): int
    {
        $baseName = Str::studly($this->argument('name'));

        if (Str::endsWith($baseName, 'Repository')) {
            $baseName = Str::beforeLast($baseName, 'Repository');
        }

        $repositoryName = $baseName.'Repository';

        $model = $this->option('model');
        $modelClass = $model ? Str::studly($model) : null;

        $paths = $this->paths($repositoryName);

        $force = (bool) $this->option('force');
        $withCache = ! $this->option('no-cache');

        $this->ensureDirectories($withCache);

        $created = 0;

        $created += $this->writeFile(
            $paths['contract'],
            $this->buildContract($repositoryName, $modelClass),
            $force
        );

        $created += $this->writeFile(
            $paths['eloquent'],
            $this->buildEloquentImplementation($repositoryName, $modelClass),
            $force
        );

        if ($withCache) {
            $created += $this->writeFile(
                $paths['cached'],
                $this->buildCachedImplementation($repositoryName, $modelClass),
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

            $this->newLine();
            $this->comment('Binding example (RepositoryServiceProvider):');
        }

        $this->ensureRepositoryServiceProviderExists();
        $this->appendBindingToRepositoryServiceProvider($repositoryName, $withCache);

        return self::SUCCESS;
    }

    private function paths(string $repositoryName): array
    {
        return [
            'contract' => app_path("Repositories/Contracts/{$repositoryName}.php"),
            'eloquent' => app_path("Repositories/Implementations/Eloquent/{$repositoryName}.php"),
            'cached' => app_path("Repositories/Implementations/Cached/{$repositoryName}.php"),
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

    private function buildContract(string $repositoryName, ?string $modelClass): string
    {
        $modelUse = $modelClass ? "use App\\Models\\{$modelClass};\n" : '';
        $methods = $this->contractMethods($modelClass);

        return <<<PHP
<?php

namespace App\Repositories\Contracts;

{$modelUse}interface {$repositoryName}
{
{$methods}}
PHP;
    }

    private function buildEloquentImplementation(string $repositoryName, ?string $modelClass): string
    {
        $contractFqn = "App\\Repositories\\Contracts\\{$repositoryName}";
        $modelUse = $modelClass ? "use App\\Models\\{$modelClass};\n" : '';
        $implements = "implements {$repositoryName}Contract";
        $contractAlias = "{$repositoryName} as {$repositoryName}Contract";
        $imports = "use {$contractFqn} as {$repositoryName}Contract;\n{$modelUse}";

        $body = $this->eloquentMethods($modelClass);

        return <<<PHP
<?php

namespace App\Repositories\Implementations\Eloquent;

{$imports}class {$repositoryName} {$implements}
{
{$body}}
PHP;
    }

    private function buildCachedImplementation(string $repositoryName, ?string $modelClass): string
    {
        $contractFqn = "App\\Repositories\\Contracts\\{$repositoryName}";
        $modelUse = $modelClass ? "use App\\Models\\{$modelClass};\n" : '';
        $imports = "use {$contractFqn} as {$repositoryName}Contract;\nuse Illuminate\\Support\\Facades\\Cache;\n{$modelUse}";

        $body = $this->cachedMethods($modelClass);

        return <<<PHP
<?php

namespace App\Repositories\Implementations\Cached;

{$imports}class {$repositoryName} implements {$repositoryName}Contract
{
    public function __construct(
        private readonly {$repositoryName}Contract \$inner
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
        return \"".Str::snake($model).':id:{$id}";
    }
';
    }

    private function bindingExample(string $repositoryName, bool $withCache): string
    {
        $contract = "\\App\\Repositories\\Contracts\\{$repositoryName}";
        $eloquent = "\\App\\Repositories\\Implementations\\Eloquent\\{$repositoryName}";
        $cached = "\\App\\Repositories\\Implementations\\Cached\\{$repositoryName}";

        if (! $withCache) {
            return "        \$this->app->bind(\{$repositoryName}::class, {$repositoryName}::class);\n"
                ."        // {$contract} => {$eloquent}\n";
        }

        return
            "        \$this->app->bind(\{$contract}::class, function (\$app) {
            return new {$cached}(
                \$app->make(\{$eloquent}::class)
            );
        });";
    }

    private function ensureProviderRegistered(): void
    {
        $providersFile = base_path('bootstrap/providers.php');

        if (! File::exists($providersFile)) {
            return;
        }

        $content = File::get($providersFile);

        if (str_contains($content, 'RepositoryServiceProvider::class')) {
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

    private function appendBindingToRepositoryServiceProvider(
        string $repositoryName,
        bool $withCache
    ): void {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (! File::exists($providerPath)) {
            return;
        }

        $content = File::get($providerPath);

        $contract = "\\App\\Repositories\\Contracts\\{$repositoryName}";
        $eloquent = "\\App\\Repositories\\Implementations\\Eloquent\\{$repositoryName}";
        $cached = "\\App\\Repositories\\Implementations\\Cached\\{$repositoryName}";

        if (str_contains($content, $contract)) {
            return;
        }

        $binding = $withCache
            ? <<<PHP

        \$this->app->bind({$contract}::class, function (\$app) {
            return new {$cached}(
                \$app->make({$eloquent}::class)
            );
        });
PHP
            : <<<PHP

        \$this->app->bind({$contract}::class, {$eloquent}::class);
PHP;

        $content = preg_replace(
            '/public function register\(\): void\s*\{\s*/',
            "public function register(): void\n    {\n{$binding}",
            $content,
            1
        );

        File::put($providerPath, $content);

        $this->info("Added binding to RepositoryServiceProvider ({$repositoryName})");
    }

    private function ensureRepositoryServiceProviderExists(): void
    {
        $providerPath = app_path('Providers/RepositoryServiceProvider.php');

        if (! File::exists($providerPath)) {
            File::ensureDirectoryExists(app_path('Providers'));

            File::put($providerPath, $this->repositoryServiceProviderStub());

            $this->info('Created RepositoryServiceProvider');
        }

        $this->ensureProviderRegistered();
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

    }
}
PHP;
    }
}
