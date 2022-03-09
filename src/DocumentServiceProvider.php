<?php

namespace Apsonex\LaravelDocument;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;

class DocumentServiceProvider extends ServiceProvider
{

    const CONFIG_PATH = __DIR__ . '/../config/document.php';

    public function boot()
    {
        $this->publishes([
            self::CONFIG_PATH => config_path('document.php'),
        ], 'media');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }


    public function register()
    {
        $this->mergeConfigFrom(self::CONFIG_PATH, 'document');

        $this->app->bind('document', function () {
            return resolve(DocumentManager::class);
        });
    }


}