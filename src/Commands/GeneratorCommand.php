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

/**
 * Class GeneratorCommand.
 */
abstract class GeneratorCommand extends Command
{

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Do not make these columns fillable in Model or views.
     *
     * @var array
     */
    protected $unwantedColumns = [
        'id',
        'password',
        'email_verified_at',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Table name from argument.
     *
     * @var string
     */
    protected $table = null;

    /**
     * Module name from argument.
     *
     * @var string
     */
    protected $module = null;

    /**
     * Formatted Class name from Table.
     *
     * @var string
     */
    protected $name = null;

    /**
     * Store the DB table columns.
     *
     * @var array
     */
    private $tableColumns = null;

    /**
     * Model Namespace.
     *
     * @var string
     */

    protected $modelNamespace = 'App\Models';

    /**
     * Request Namespace.
     *
     * @var string
     */

    protected $requestNamespace = 'App\Http\Requests';

    /**
     * Request Namespace.
     *
     * @var string
     */

    protected $repositoryNamespace = 'App\Repositories';

    /**
     * Controller Namespace.
     *
     * @var string
     */
    protected $controllerNamespace = 'App\Http\Controllers';

    protected $migratePath = 'database\migrations';
    /**
     * Application Layout
     *
     * @var string
     */
    protected $layout = 'layouts.app';

    /**
     * Custom Options name
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new controller creator command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $files
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->unwantedColumns = $this->unwantedColumns;
        $this->modelNamespace = $this->modelNamespace;
        $this->repositoryNamespace = $this->repositoryNamespace;
        $this->requestNamespace = $this->requestNamespace;
        $this->controllerNamespace = $this->controllerNamespace;
        $this->layout = config('crud.layout', $this->layout);
    }

    /**
     * Generate the controller.
     *
     * @return $this
     */
    abstract protected function buildController();

    /**
     * Generate the Model.
     *
     * @return $this
     */
    abstract protected function buildModel();

    /**
     * Generate the views.
     *
     * @return $this
     */
    abstract protected function buildViews();

    /**
     * Build the directory if necessary.
     *
     * @param string $path
     *
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Write the file/Class.
     *
     * @param $path
     * @param $content
     */
    protected function write($path, $content)
    {
        $this->files->put($path, $content);
    }

    /**
     * Get the stub file.
     *
     * @param string $type
     * @param boolean $content
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
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

    /**
     * @param $no
     *
     * @return string
     */
    private function _getSpace($no = 1)
    {
        $tabs = '';
        for ($i = 0; $i < $no; $i++) {
            $tabs .= "\t";
        }

        return $tabs;
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getControllerPath($name)
    {
        return $this->path($this->_getNamespacePath($this->controllerNamespace) . "{$name}Controller.php");
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getModelPath($name)
    {
        // $name = strtolower(Str::plural($name));
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->modelNamespace) . "{$name}.php"));
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getRequestPath($name)
    {
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->requestNamespace) . "{$name}Request.php"));
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getRepositoryPath($name)
    {
        return $this->makeDirectory($this->path($this->_getNamespacePath($this->repositoryNamespace) . "{$name}Repository.php"));
    }

    /**
     * @param $name
     *
     * @return string
     */
    protected function _getMigrationPath($name)
    {
        $name = strtolower(Str::plural($name));
        return $this->makeDirectory(base_path($this->_getNamespacePath($this->migratePath)  . date('Y_m_d_hmi_') . 'create_' . "{$name}_table.php"));
    }

    /**
     * Get the path from namespace.
     *
     * @param $namespace
     *
     * @return string
     */
    private function _getNamespacePath($namespace)
    {
        $str = Str::start(Str::finish(Str::after($namespace, 'App'), '\\'), '\\');

        return str_replace('\\', '/', $str);
    }

    /**
     * Get the default layout path.
     *
     * @return string
     */
    private function _getLayoutPath()
    {
        return $this->makeDirectory(resource_path("/views/layouts/app.blade.php"));
    }

    /**
     * @param $view
     *
     * @return string
     */
    protected function _getViewPath($view)
    {
        $name = Str::kebab($this->name);

        if (!empty($this->module)) {
            return $this->makeDirectory($this->path('Modules/' . $this->module . "/Resources/views/{$name}/{$view}.blade.php"));
        } else {
            return $this->makeDirectory(resource_path("/views/{$name}/{$view}.blade.php"));
        }
    }

    /**
     * Build the replacement.
     *
     * @return array
     */
    protected function buildReplacements()
    {

        return [
            '{{layout}}' => $this->layout,
            '{{modelName}}' => $this->name,
            '{{modelTitle}}' => Str::title(Str::snake($this->name, ' ')),
            '{{modelNamespace}}' => $this->modelNamespace,
            '{{repositoryNamespace}}' => $this->repositoryNamespace,
            '{{requestNamespace}}' => $this->requestNamespace,
            '{{controllerNamespace}}' => $this->controllerNamespace,
            '{{modelNamePluralLowerCase}}' => Str::camel(Str::plural($this->name)),
            '{{modelNamePluralUpperCase}}' => ucfirst(Str::plural($this->name)),
            '{{modelNameLowerCase}}' => Str::camel($this->name),
            '{{modelRoute}}' =>   Str::kebab(Str::plural($this->name)),
            '{{modelView}}' => Str::kebab($this->name),
        ];
    }

    /**
     * Build the form fields for form.
     *
     * @param $title
     * @param $column
     * @param string $type
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     *
     */
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

    /**
     * @param $title
     *
     * @return mixed
     */
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

    /**
     * @param $column
     *
     * @return mixed
     */
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

    /**
     * Make layout if not exists.
     *
     * @throws \Exception
     */
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

    /**
     * Get the DB Table columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        if (empty($this->tableColumns)) {
            $this->tableColumns = DB::select('SHOW COLUMNS FROM ' . $this->table);
        }

        return $this->tableColumns;
    }

    /**
     * @return array
     */
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

    /**
     * Make model attributes/replacements.
     *
     * @return array
     */
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

    /**
     * Make Request attributes/replacements.
     *
     * @return array
     */
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


    /**
     * Make Request attributes/replacements.
     *
     * @return array
     */
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

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return trim($this->argument('name'));
    }


    protected function getModuleInput()
    {
        return trim($this->argument('module'));
    }

    /**
     * Build the options
     *
     * @return $this|array
     */
    // protected function buildOptions()
    // {
    //     $route = $this->option('route');

    //     if (!empty($route)) {
    //         $this->options['route'] = $route;
    //     }

    //     return $this;
    // }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    /**
     * Is Table exist in DB.
     *
     * @return mixed
     */
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
