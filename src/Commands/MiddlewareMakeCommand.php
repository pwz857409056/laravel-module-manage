<?php

namespace Powitz\LaravelModuleManage\Commands;

use Illuminate\Support\Str;
use Powitz\LaravelModuleManage\Support\Config\GenerateConfigReader;
use Powitz\LaravelModuleManage\Support\Stub;
use Powitz\LaravelModuleManage\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MiddlewareMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument name.
     *
     * @var string
     */
    protected string $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new middleware class for the specified module.';


    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.filter.namespace') ?: $module->config('paths.generator.filter.path', 'Http/Middleware');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['master', null, InputOption::VALUE_NONE, 'Indicates the master middleware', null],
        ];
    }

    /**
     * @return mixed
     */
    protected function getTemplateContents(): mixed
    {
        $module = $this->laravel['modules'];
        $stub = $this->option('master') ? '/scaffold' . DIRECTORY_SEPARATOR . $this->argument('name') . '.stub' : DIRECTORY_SEPARATOR . 'middleware.stub';
        return (new Stub($stub, [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getFileName(),
            'LOWER_NAME' => $module->getLowerName(),
        ]))->render();

    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());
        $middlewarePath = GenerateConfigReader::read('filter');

        return $path . $middlewarePath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }
}
