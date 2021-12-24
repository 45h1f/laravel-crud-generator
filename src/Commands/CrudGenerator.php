<?php

namespace Ashiful\Crud\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;


class CrudGenerator extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud
                            {name : Table name}
                            {module? : Module name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create bootstrap CRUD operations';

    /**
     * Execute the console command.
     *
     * @return bool|null
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    public function handle()
    {
        $this->info('Running Crud Generator ...');

        $this->table = $this->getNameInput();
        $this->module = $this->getModuleInput();

        if (!empty($this->module) && !Module::has($this->module)) {
            $this->error("`{$this->module}` module not exist");
            return false;
        };
        // If table not exist in DB return
        if (!$this->tableExists()) {
            $this->error("`{$this->table}` table not exist");

            return false;
        }

        // Build the class name from table name
        $this->name = $this->_buildClassName();


        if (!empty($this->module)) {
            $this->controllerNamespace = "Modules\\" . $this->module . "\Http\Controllers";
            $this->modelNamespace = "Modules\\" . $this->module . "\Models";
            $this->requestNamespace = "Modules\\" . $this->module . "\Http\Requests";
            $this->repositoryNamespace = "Modules\\" . $this->module . "\Repositories";
            $this->migratePath = "Modules\\" . $this->module . "\Database\Migrations";
        };

        // Generate the crud
        $this
            // ->buildOptions()
            ->buildController()
            ->buildModel()
            ->buildRequest()
            ->buildRepository()
            ->buildMigration()
            ->buildViews()
            ->buildRoute();

        $this->info('Created Successfully.');

        return true;
    }

    /**
     * Build the Controller Class and save in app/Http/Controllers.
     *
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function buildController()
    {
        $controllerPath = $this->_getControllerPath($this->name);

        if ($this->files->exists($controllerPath) && $this->ask('Already exist Controller. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Controller ...');

        $replace = $this->buildReplacements();

        $controllerTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Controller')
        );

        $this->write($controllerPath, $controllerTemplate);

        return $this;
    }

    /**
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
    protected function buildModel()
    {
        $modelPath = $this->_getModelPath($this->name);

        if ($this->files->exists($modelPath) && $this->ask('Already exist Model. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Model ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->modelReplacements());
        $modelTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Model')
        );

        $this->write($modelPath, $modelTemplate);

        return $this;
    }

    /**
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */

    protected function buildRequest()
    {
        $requestPath = $this->_getRequestPath($this->name);

        if ($this->files->exists($requestPath) && $this->ask('Already exist Request. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Request ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->requestReplacements());

        $requestTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Request')
        );

        $this->write($requestPath, $requestTemplate);

        return $this;
    }

    /**
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */

    protected function buildRepository()
    {
        $requestPath = $this->_getRepositoryPath($this->name);

        if ($this->files->exists($requestPath) && $this->ask('Already exist Repository. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Repository ...');

        // Make the models attributes and replacement
        $replace = $this->buildReplacements();

        $requestTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Repository')
        );

        $this->write($requestPath, $requestTemplate);

        return $this;
    }


    protected function buildRoute()
    {
        $name = Str::lower(Str::plural($this->name));




        if (!empty($this->module)) {
            $route = 'Route::resource("' . $name . '", \'' . $this->name . 'Controller\');';
            $route_path = 'Modules/' . $this->module . '/Routes/web.php';
        } else {
            $route = 'Route::resource("' . $name . '", \App\Http\Controllers\\' . $this->name . 'Controller::class);';
            $route_path = 'routes/web.php';
        }

        $route_content = file_get_contents($route_path);
        $keyPosition = strpos($route_content, "{$route}");
        if (is_bool($keyPosition)) {
            $route_content .= "\n";
            $route_content .= $route;
            file_put_contents($route_path, $route_content);
        }
        return $this;
    }

    /**
     * @return $this
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     * @throws \Exception
     */
    protected function buildViews()
    {
        $this->info('Creating Views ...');

        $tableHead = "\n";
        $tableBody = "\n";
        $viewRows = "\n";
        $form = "\n";

        foreach ($this->getFilteredColumns() as $column) {
            $title = Str::title(str_replace('_', ' ', $column));

            $tableHead .= $this->getHead($title);
            $tableBody .= $this->getBody($column);
            $viewRows .= $this->getField($title, $column, 'view-field');
            $form .= $this->getField($title, $column, 'form-field');
        }

        $replace = array_merge($this->buildReplacements(), [
            '{{tableHeader}}' => $tableHead,
            '{{tableBody}}' => $tableBody,
            '{{viewRows}}' => $viewRows,
            '{{form}}' => $form,
        ]);

        $this->buildLayout();

        foreach (['index', 'create', 'edit', 'form', 'show'] as $view) {
            $viewTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub("views/{$view}")
            );

            $this->write($this->_getViewPath($view), $viewTemplate);
        }

        return $this;
    }

    /**
     * Make the class name from table name.
     *
     * @return string
     */
    private function _buildClassName()
    {
        return Str::studly(Str::singular($this->table));
    }

    private function buildMigration()
    {
        $migrationPath = $this->_getMigrationPath($this->name);
        if ($this->files->exists($migrationPath) && $this->ask('Already exist Migration. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }

        $this->info('Creating Migration ...');

        // Make the models attributes and replacement
        $replace = array_merge($this->buildReplacements(), $this->migrationReplacements());

        $modelTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Migration')
        );

        $this->write($migrationPath, $modelTemplate);

        return $this;
    }
}
