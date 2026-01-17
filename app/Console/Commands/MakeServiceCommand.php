<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    protected $signature = 'make:service
        {name : Service name (e.g. CartService or Cart)}
        {--force : Overwrite existing file}';

    protected $description = 'Create service class in App\Services';

    public function handle(): int
    {
        $inputName = Str::studly($this->argument('name'));

        if (! Str::endsWith($inputName, 'Service')) {
            $inputName .= 'Service';
        }

        $path = app_path("Services/{$inputName}.php");

        if (File::exists($path) && ! $this->option('force')) {
            $this->warn("Skip (exists): app/Services/{$inputName}.php");

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(app_path('Services'));
        File::put($path, $this->stub($inputName));

        $this->info("Service created: app/Services/{$inputName}.php");

        return self::SUCCESS;
    }

    private function stub(string $className): string
    {
        $base = Str::replaceEnd('Service', '', $className);

        return <<<PHP
<?php

namespace App\Services;

class {$className}
{
    // implement {$base} business logic here
}
PHP;
    }
}
