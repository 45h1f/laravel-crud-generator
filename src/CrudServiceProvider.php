<?php

namespace Ashiful\Crud;

use Ashiful\Crud\Commands\CrudGenerator;
use Illuminate\Support\ServiceProvider;

class CrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudGenerator::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/config/crud.php' => config_path('crud.php'),
            __DIR__.'/stubs/views/create.stub' => base_path('stubs/crud/views/create.stub'),
            __DIR__.'/stubs/views/edit.stub' => base_path('stubs/crud/views/edit.stub'),
            __DIR__.'/stubs/views/form-field.stub' => base_path('stubs/crud/views/form-field.stub'),
            __DIR__.'/stubs/views/form.stub' => base_path('stubs/crud/views/form.stub'),
            __DIR__.'/stubs/views/index.stub' => base_path('stubs/crud/views/index.stub'),
            __DIR__.'/stubs/views/show.stub' => base_path('stubs/crud/views/show.stub'),
            __DIR__.'/stubs/views/view-field.stub' => base_path('stubs/crud/views/view-field.stub'),
            __DIR__.'/stubs/Controller.stub' => base_path('stubs/crud/Controller.stub'),
            __DIR__.'/stubs/Migration.stub' => base_path('stubs/crud/Migration.stub'),
            __DIR__.'/stubs/Model.stub' => base_path('stubs/crud/Model.stub'),
            __DIR__.'/stubs/Factory.stub' => base_path('stubs/crud/Factory.stub'),
            __DIR__.'/stubs/Seeder.stub' => base_path('stubs/crud/Seeder.stub'),
            __DIR__.'/stubs/Policy.stub' => base_path('stubs/crud/Policy.stub'),
            __DIR__.'/stubs/Repository.stub' => base_path('stubs/crud/Repository.stub'),
            __DIR__.'/stubs/Request.stub' => base_path('stubs/crud/Request.stub'),
            __DIR__.'/stubs/Test.stub' => base_path('stubs/crud/Test.stub'),
            __DIR__.'/stubs/components/action.stub' => base_path('stubs/components/action.stub'),
            __DIR__.'/stubs/components/input-error.stub' => base_path('stubs/components/input-error.stub'),
            __DIR__.'/stubs/components/input-label.stub' => base_path('stubs/components/input-label.stub'),
            __DIR__.'/stubs/components/status.stub' => base_path('stubs/components/status.stub'),
            __DIR__.'/stubs/components/text-input.stub' => base_path('stubs/components/text-input.stub'),
        ], 'crud');
    }

    public function register()
    {
        //
    }
}
