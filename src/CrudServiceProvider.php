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
            __DIR__ . '/stubs/views/create.stub' => base_path('stubs/crud/views/create.stub'),
            __DIR__ . '/stubs/views/edit.stub' => base_path('stubs/crud/views/edit.stub'),
            __DIR__ . '/stubs/views/form-field.stub' => base_path('stubs/crud/views/form-field.stub'),
            __DIR__ . '/stubs/views/form.stub' => base_path('stubs/crud/views/form.stub'),
            __DIR__ . '/stubs/views/index.stub' => base_path('stubs/crud/views/index.stub'),
            __DIR__ . '/stubs/views/show.stub' => base_path('stubs/crud/views/show.stub'),
            __DIR__ . '/stubs/views/view-field.stub' => base_path('stubs/crud/views/view-field.stub'),
            __DIR__ . '/stubs/Controller.stub' => base_path('stubs/crud/Controller.stub'),
            __DIR__ . '/stubs/Migration.stub' => base_path('stubs/crud/Migration.stub'),
            __DIR__ . '/stubs/Model.stub' => base_path('stubs/crud/Model.stub'),
            __DIR__ . '/stubs/Repository.stub' => base_path('stubs/crud/Repository.stub'),
            __DIR__ . '/stubs/Request.stub' => base_path('stubs/crud/Request.stub'),
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
