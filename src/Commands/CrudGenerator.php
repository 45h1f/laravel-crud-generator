<?php

namespace Ashiful\Crud\Commands;

use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;


class CrudGenerator extends GeneratorCommand
{
    protected $signature = 'make:crud
                            {name : Table name}
                            {module? : Module name}';

    protected $description = 'Create CRUD operations';

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
            $this->interfaceNamespace = "Modules\\" . $this->module . "\Interfaces";
            $this->migratePath = "Modules\\" . $this->module . "\Database\Migrations";
            $this->providerNamespace = "Modules\\" . $this->module . "\Providers";

            $this->providerFileLocation = 'Modules/' . $this->module . '/Providers/RepositoryServiceProvider.php';
            $this->providerRegisterFileLocation = 'Modules/' . $this->module . '/Providers/' . $this->module . 'ServiceProvider.php';;
        };

        // Generate the crud
        $this
            ->buildController()
            ->buildModel()
            ->buildRequest()
            ->buildRepository()
            ->buildInterface()
            ->buildProviderWithRegister()
            ->buildMigration()
            ->buildViews()
            ->buildRoute();

        $this->info('Created Successfully.');

        return true;
    }

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

    protected function buildRepository()
    {
        $repositoryPath = $this->_getRepositoryPath($this->name);

        $baseRepository = $this->baseRepositoryFileLocation;
        $this->write($baseRepository, $this->getStub('BaseRepository'));;
        if ($this->files->exists($repositoryPath) && $this->ask('Already exist Repository. Do you want overwrite (y/n)?', 'y') == 'n') {
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

        $this->write($repositoryPath, $requestTemplate);

        return $this;
    }

    protected function buildInterface()
    {
        $interfacePath = $this->_getInterfacePath($this->name);

        $baseInterface = $this->baseInterfaceFileLocation;
        $this->write($baseInterface, $this->getStub('BaseInterface'));


        if ($this->files->exists($interfacePath) && $this->ask('Already exist Interface. Do you want overwrite (y/n)?', 'y') == 'n') {
            return $this;
        }


        $this->info('Creating Interface ...');

        // Make the models attributes and replacement
        $replace = $this->buildReplacements();

        $requestTemplate = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub('Interface')
        );

        $this->write($interfacePath, $requestTemplate);

        return $this;
    }

    public function buildProviderWithRegister()
    {
        $providerFileLocation = $this->providerFileLocation;
        $providerRegisterFileLocation = $this->providerRegisterFileLocation;


        if (!$this->files->exists($providerFileLocation)) {
            $replace = $this->buildReplacements();

            $RepositoryServiceProviderTemplate = str_replace(
                array_keys($replace),
                array_values($replace),
                $this->getStub('RepositoryServiceProvider')
            );
            $this->write($providerFileLocation, $RepositoryServiceProviderTemplate);


            $regProviderText = '        $this->app->register(RepositoryServiceProvider::class);';
            $providerRegisterContent = file_get_contents($this->providerRegisterFileLocation);

            $keyPosition = strpos($providerRegisterContent, "{$regProviderText}");

            if (!$keyPosition) {
                $regText = 'public function register(): void';

                $regTextCheck = strpos($providerRegisterContent, "{$regText}");

                $begin = substr($providerRegisterContent, 0, $regTextCheck + 38);
                $end = substr($providerRegisterContent, $regTextCheck + 38);
                $providerRegisterContentUpdate = $begin . "\n" . $regProviderText . "\n" . $end;
                file_put_contents($providerRegisterFileLocation, $providerRegisterContentUpdate);
            }
        }
        $this->registerInRepo();
        return $this;
    }

    public function registerInRepo()
    {
        $bindText = '       $this->app->bind(' . $this->name . 'RepositoryInterface::class, ' . $this->name . 'Repository::class);';

        $providerFileLocationContent = file_get_contents($this->providerFileLocation);
        $keyPosition = strpos($providerFileLocationContent, "{$bindText}");

        if (is_bool($keyPosition)) {
            $regText = 'public function register(): void';
            $regTextCheck = strpos($providerFileLocationContent, "{$regText}");
            $begin = substr($providerFileLocationContent, 0, $regTextCheck + 40);
            $end = substr($providerFileLocationContent, $regTextCheck + 40);

            $providerRegisterContentUpdate = $begin . "\n" . $bindText . "\n" . $end;

            file_put_contents($this->providerFileLocation, $providerRegisterContentUpdate);

            $useInterfaceText = ' use ' . $this->interfaceNamespace . '\\' . $this->name . 'RepositoryInterface; ' . "\n" .
                ' use ' . $this->repositoryNamespace . '\\' . $this->name . 'Repository;';

            $providerFileLocationContent = file_get_contents($this->providerFileLocation);
            $regText = 'use App\Repositories\BaseRepository;';
            $regTextCheck = strpos($providerFileLocationContent, "{$regText}");
            $begin = substr($providerFileLocationContent, 0, $regTextCheck - 1);
            $end = substr($providerFileLocationContent, $regTextCheck - 1);
            $providerRegisterContentUpdate = $begin . "\n" . $useInterfaceText . "\n" . $end;
            file_put_contents($this->providerFileLocation, $providerRegisterContentUpdate);

        }


    }

    protected function buildRoute()
    {
        $name = Str::kebab(Str::plural($this->name));


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
