<?php

namespace Ashiful\Crud\Commands;

use Ashiful\Crud\ModelGenerator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

abstract class GeneratorCommand extends Command
{
    protected $files;
    protected $unwantedColumns = [
        'id',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $table = null;

    protected $module = null;

    protected $name = null;

    private $tableColumns = null;

    protected $modelNamespace = 'App\Models';

    protected $requestNamespace = 'App\Http\Requests';

    protected $repositoryNamespace = 'App\Repositories';

    protected $interfaceNamespace = 'App\Interfaces';

    protected $controllerNamespace = 'App\Http\Controllers';

    protected $providerNamespace = 'App\Providers';

    protected $migratePath = 'database\migrations';

    protected $layout = 'layouts.app';

    protected $options = [];

    protected $baseRepositoryFileLocation = 'app/Repositories/BaseRepository.php';

    protected $baseInterfaceFileLocation = 'app/Interfaces/BaseRepositoryInterface.php';

    protected $providerFileLocation = 'app/Providers/RepositoryServiceProvider.php';

    protected $providerRegisterFileLocation = 'app/Providers/AppServiceProvider.php';


    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->layout = config('crud.layout', $this->layout);
    }

    abstract protected function buildController();

    abstract protected function buildModel();

    abstract protected function buildViews();

    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    protected function write($path, $content)
    {
        $this->files->put($path, $content);
    }

    protected function getStub($type, $content = true)
    {
        $stub_path = config('crud.stub_path', 'default');
        if ($stub_path == 'default') {
            $stub_path = __DIR__ . '/../stubs/';
        }

        $path = Str::finish($stub_path, '/') . "{$type}.stub";

        if (!$content) {
            return $path;
        }

        return $this->files->get($path);
    }

    private function _getSpace($no = 1)
    {
        $tabs = '';
        for ($i = 0; $i < $no; $i++) {
            $tabs .= "\t";
        }

        return $tabs;
    }

    protected function _getControllerPath($name)
    {
        return $this->path($this->_getNamespacePath($this->controllerNamespace) . "{$name}Controller.php");
    }

    protected function _getModelPath($name)
    {
        // $name = strtolower(Str::plural($name));
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->modelNamespace) . "{$name}.php"));
    }

    protected function _getRequestPath($name)
    {
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->requestNamespace) . "{$name}Request.php"));
    }

    protected function _getRepositoryPath($name)
    {
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->repositoryNamespace) . "{$name}Repository.php"));
    }

    protected function _getInterfacePath($name)
    {
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->interfaceNamespace) . "{$name}RepositoryInterface.php"));
    }

    protected function _getMigrationPath($name)
    {
        $name = strtolower(Str::plural($name));
        return $this->makeDirectory(base_path($this->_getNamespacePath($this->migratePath) . date('Y_m_d_hmi_') . 'create_' . "{$name}_table.php"));
    }

    private function _getNamespacePath($namespace)
    {
        $str = Str::start(Str::finish(Str::after($namespace, 'App'), '\\'), '\\');

        return str_replace('\\', '/', $str);
    }

    private function _getLayoutPath()
    {
        return $this->makeDirectory(resource_path("/views/layouts/app.blade.php"));
    }

    protected function _getViewPath($view)
    {
        $name = Str::kebab($this->name);

        if (!empty($this->module)) {
            return $this->makeDirectory($this->path('Modules/' . $this->module . "/Resources/views/{$name}/{$view}.blade.php"));
        } else {
            return $this->makeDirectory(resource_path("/views/{$name}/{$view}.blade.php"));
        }
    }

    protected function buildReplacements()
    {
        $viewPath = Str::kebab($this->name);
        if (!empty($this->module)) {
            $viewPath = strtolower($this->module) . '::' . $viewPath;
        }

        return [
            '{{layout}}' => $this->layout,
            '{{modelName}}' => $this->name,
            '{{modelTitle}}' => Str::title(Str::snake($this->name, ' ')),
            '{{modelNamespace}}' => $this->modelNamespace,
            '{{repositoryNamespace}}' => $this->repositoryNamespace,
            '{{interfaceNamespace}}' => $this->interfaceNamespace,
            '{{requestNamespace}}' => $this->requestNamespace,
            '{{controllerNamespace}}' => $this->controllerNamespace,
            '{{providerNamespace}}' => $this->providerNamespace,
            '{{modelNamePluralLowerCase}}' => Str::camel(Str::plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(Str::plural($this->name)),
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' => Str::kebab(Str::plural($this->name)),
            '{{modelView}}' => $viewPath,
        ];
    }


    protected function getField($title, $column, $type = 'form-field')
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
            '{{column}}' => $column,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->getStub("views/{$type}")
        );
    }

    protected function getHead($title)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{title}}' => $title,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(10) . '<th>{{title}}</th>' . "\n"
        );
    }

    protected function getBody($column)
    {
        $replace = array_merge($this->buildReplacements(), [
            '{{column}}' => $column,
        ]);

        return str_replace(
            array_keys($replace),
            array_values($replace),
            $this->_getSpace(11) . '<td>{{ ${{modelNameLowerCase}}->{{column}} }}</td>' . "\n"
        );
    }

    protected function buildLayout(): void
    {
        if (!(view()->exists($this->layout))) {

            $this->info('Creating Layout ...');

            if ($this->layout == 'layouts.app') {
                $this->files->copy($this->getStub('layouts/app', false), $this->_getLayoutPath());
            } else {
                throw new \Exception("{$this->layout} layout not found!");
            }
        }
    }

    protected function getColumns()
    {
        if (empty($this->tableColumns)) {
            $this->tableColumns = DB::select('SHOW COLUMNS FROM ' . $this->table);
        }

        return $this->tableColumns;
    }

    protected function getFilteredColumns()
    {
        $unwanted = $this->unwantedColumns;
        $columns = [];

        foreach ($this->getColumns() as $column) {
            $columns[] = $column->Field;
        }

        return array_filter($columns, function ($value) use ($unwanted) {
            return !in_array($value, $unwanted);
        });
    }

    protected function modelReplacements()
    {
        $properties = '*';
        $rulesArray = [];
        $softDeletesNamespace = $softDeletes = '';

        foreach ($this->getColumns() as $value) {
            $properties .= "\n * @property $$value->Field";

            if ($value->Null == 'NO') {
                $rulesArray[$value->Field] = 'required';
            } else {
                $rulesArray[$value->Field] = 'nullable';
            }

            if ($value->Field == 'deleted_at') {
                $softDeletesNamespace = "use Illuminate\Database\Eloquent\SoftDeletes;\n";
                $softDeletes = "use SoftDeletes;\n";
            }
        }
        $rules = function () use ($rulesArray) {
            $rules = '';
            // Exclude the unwanted rulesArray
            $rulesArray = Arr::except($rulesArray, $this->unwantedColumns);
            // Make rulesArray
            foreach ($rulesArray as $col => $rule) {
                $rules .= "\n\t\t'{$col}' => '{$rule}',";
            }

            return $rules;
        };

        $fillable = function () {

            /** @var array $filterColumns Exclude the unwanted columns */
            $filterColumns = $this->getFilteredColumns();

            // Add quotes to the unwanted columns for fillable
            array_walk($filterColumns, function (&$value) {
                $value = "'" . $value . "'";
            });

            // CSV format
            return implode(',', $filterColumns);
        };

        $properties .= "\n *";

        list($relations, $properties) = (new ModelGenerator($this->table, $properties, $this->modelNamespace))->getEloquentRelations();

        return [
            '{{fillable}}' => $fillable(),
            '{{rules}}' => $rules(),
            '{{relations}}' => $relations,
            '{{properties}}' => $properties,
            '{{softDeletesNamespace}}' => $softDeletesNamespace,
            '{{softDeletes}}' => $softDeletes,
        ];
    }

    protected function requestReplacements()
    {
        $rulesArray = [];

        foreach ($this->getColumns() as $value) {

            if ($value->Null == 'NO') {
                $rulesArray[$value->Field] = 'required';
            } else {
                $rulesArray[$value->Field] = 'nullable';
            }
        }

        $rules = function () use ($rulesArray) {
            $rules = '';
            // Exclude the unwanted rulesArray
            $rulesArray = Arr::except($rulesArray, $this->unwantedColumns);
            // Make rulesArray
            foreach ($rulesArray as $col => $rule) {
                $rules .= "\n\t\t'{$col}' => '{$rule}',";
            }

            return $rules;
        };

        return [
            '{{rules}}' => $rules(),
        ];
    }

    protected function migrationReplacements()
    {

        $rulesArray = [];

        foreach ($this->getColumns() as $value) {
            $type = str_replace('(', '', $value->Type);
            $type = str_replace(')', '', $type);
            $type = preg_replace('/[0-9]+/', '', $type);
            if ($type == "varchar") {
                $type = 'string';
            } elseif ($type == "int") {
                $type = 'integer';
            }
            if ($value->Null != 'NO') {
                $rulesArray[$value->Field]['nullable'] = true;
            } else {
                $rulesArray[$value->Field]['nullable'] = false;
            }
            $rulesArray[$value->Field]['type'] = $type;
            $rulesArray[$value->Field]['default'] = $value->Default;
        }

        $rulesArray = Arr::except($rulesArray, $this->unwantedColumns);


        $tableSchema = '';
        foreach ($rulesArray as $col => $rule) {
            $nullable = '';
            if ($rule['nullable']) {
                $nullable = '->nullable()';
            }
            $default = '';
            if (!empty($rule['default'])) {
                $default = '->default("' . $rule['default'] . '")';
            }
            $tableSchema .= '$table->' . $rule['type'] . '("' . $col . '")' . $nullable . $default . ';
            ';
        }

        $table = $this->table;
        return [
            '{{table}}' => $table,
            '{{up}}' => $tableSchema,
        ];
    }

    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }


    protected function getModuleInput()
    {
        return trim($this->argument('module'));
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }


    protected function tableExists()
    {
        return Schema::hasTable($this->table);
    }

    protected function path($url)
    {
        if (!empty($this->module)) {
            return base_path($url);
        } else {
            return app_path($url);
        }
    }
}
