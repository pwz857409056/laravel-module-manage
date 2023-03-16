<?php

namespace Powitz\LaravelModuleManage\Commands;

use Illuminate\Support\Str;
use Powitz\LaravelModuleManage\Support\Config\GenerateConfigReader;
use Powitz\LaravelModuleManage\Support\Stub;
use Powitz\LaravelModuleManage\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class BaseServiceMakeCommand extends GeneratorCommand
{
    use ModuleCommandTrait;

    /**
     * The name of argument being used.
     *
     * @var string
     */
    protected string $argumentName = 'name';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'module:make-base-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new base-service for the specified module.';

    /**
     * @return string
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('service');

        return $path . $generatorPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules'];
        return (new Stub('/scaffold/base-service.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
            'MODULE' => $this->getModuleName(),
        ]))->render();
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the base-service class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.service.namespace') ?: $module->config('paths.generator.service.path', 'Services');
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }
}
