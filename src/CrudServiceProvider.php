<?php

namespace Ashiful\Crud;

use Ashiful\Crud\Commands\CrudGenerator;
use Illuminate\Support\ServiceProvider;

/**
 * Class CrudServiceProvider.
 */
class CrudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/config/crud.php' => config_path('crud.php'),
            __DIR__ . '/stubs/views/create.stub' => resource_path('views/vendor/crud/create.stub'),
            __DIR__ . '/stubs/views/edit.stub' => resource_path('views/vendor/crud/edit.stub'),
            __DIR__ . '/stubs/views/form-field.stub' => resource_path('views/vendor/crud/form-field.stub'),
            __DIR__ . '/stubs/views/form.stub' => resource_path('views/vendor/crud/form.stub'),
            __DIR__ . '/stubs/views/form.stub' => resource_path('views/vendor/crud/form.stub'),
        ], 'crud');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
