<?php

namespace Powitz\LaravelModuleManage\Commands;

use Illuminate\Support\Str;
use Powitz\LaravelModuleManage\Support\Config\GenerateConfigReader;
use Powitz\LaravelModuleManage\Support\Stub;
use Powitz\LaravelModuleManage\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class FormRequestMakeCommand extends GeneratorCommand
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
    protected $name = 'module:make-form-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new middleware class for the specified module.';

    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.request.namespace') ?: $module->config('paths.generator.request.path', 'Http/Requests');
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
     * @return mixed
     */
    protected function getTemplateContents(): mixed
    {
        $module = $this->laravel['modules'];

        return (new Stub('/request/form-request.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'CLASS' => $this->getClass(),
            'LOWER_NAME' => $module->getLowerName(),
        ]))->render();
    }

    /**
     * @return mixed
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $middlewarePath = GenerateConfigReader::read('request');

        return $path . $middlewarePath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return Str::studly('form-request');
    }
}
