<?php

use App\Console\Commands\MakeRepositoryCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('make:repository {name}', function () {
    $this->call(MakeRepositoryCommand::class, [
        'name' => $this->argument('name'),
    ]);
})->purpose('Create a new repository class');
