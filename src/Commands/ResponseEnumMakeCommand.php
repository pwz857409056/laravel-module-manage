<?php

namespace Powitz\LaravelModuleManage\Commands;

use Illuminate\Support\Str;
use Powitz\LaravelModuleManage\Support\Config\GenerateConfigReader;
use Powitz\LaravelModuleManage\Support\Stub;
use Powitz\LaravelModuleManage\Traits\ModuleCommandTrait;
use Symfony\Component\Console\Input\InputArgument;

class ResponseEnumMakeCommand extends GeneratorCommand
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
    protected $name = 'module:make-response-enum';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate new response-enum for the specified module.';

    /**
     * @return string
     */
    protected function getDestinationFilePath(): string
    {
        $path = $this->laravel['modules']->getModulePath($this->getModuleName());

        $generatorPath = GenerateConfigReader::read('response-enum');

        return $path . $generatorPath->getPath() . '/' . $this->getFileName() . '.php';
    }

    /**
     * @return string
     */
    protected function getTemplateContents(): string
    {
        $module = $this->laravel['modules'];
        return (new Stub('/scaffold/response-enum.stub', [
            'NAMESPACE' => $this->getClassNamespace($module),
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
            ['name', InputArgument::REQUIRED, 'The name of the controller class.'],
            ['module', InputArgument::OPTIONAL, 'The name of module will be used.'],
        ];
    }

    public function getDefaultNamespace(): string
    {
        $module = $this->laravel['modules'];

        return $module->config('paths.generator.response-enum.namespace') ?: $module->config('paths.generator.response-enum.path', 'Helpers');
    }

    /**
     * @return string
     */
    private function getFileName(): string
    {
        return Str::studly($this->argument('name'));
    }
}
