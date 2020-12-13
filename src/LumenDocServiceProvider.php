<?php

namespace MrBugMiner\LumenDoc;

use Illuminate\Support\ServiceProvider;
use MrBugMiner\LumenDoc\Console\ScanCommand;
use MrBugMiner\LumenDoc\Console\GenerateCommand;

class LumenDocServiceProvider extends ServiceProvider
{

    public function register()
    {

        // Merge Config File
        $this->mergeConfigFrom(
            __DIR__ . '/config/lumen-doc.php', 'lumen-doc'
        );

    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            // Publish Config File
            $this->publishes([
                __DIR__ . '/config/lumen-doc.php' => config_path('lumen-doc.php'),
            ], 'config');

            // Commands
            $this->commands([
                ScanCommand::class,
                GenerateCommand::class,
            ]);

        }
    }

}