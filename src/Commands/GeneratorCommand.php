<?php

namespace Powitz\LaravelModuleManage\Commands;

use Illuminate\Console\Command;
use Powitz\LaravelModuleManage\Exceptions\FileAlreadyExistException;
use Powitz\LaravelModuleManage\Generators\FileGenerator;

abstract class GeneratorCommand extends Command
{

    /**
     * The name of 'name' argument.
     *
     * @var string
     */
    protected string $argumentName = '';

    /**
     * Get template contents.
     *
     * @return mixed
     */
    abstract protected function getTemplateContents(): mixed;

    /**
     * Get the destination file path.
     *
     * @return string
     */
    abstract protected function getDestinationFilePath(): string;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = $this->argument('module');
        if (!$moduleName) {
            $this->components->error('You should input the name of module');
            return E_ERROR;
        }

        if (!app('modules')->has($moduleName)) {
            $this->components->error("{$moduleName} Module not found, use command module:make to create module first");
            return E_ERROR;
        }

        app('modules')->setModuleName($moduleName);
        $path = str_replace('\\', '/', $this->getDestinationFilePath());

        if (!$this->laravel['files']->isDirectory($dir = dirname($path))) {
            $this->laravel['files']->makeDirectory($dir, 0777, true);
        }

        $contents = $this->getTemplateContents();

        try {
            $overwriteFile = $this->hasOption('force') ? $this->option('force') : false;
            (new FileGenerator($path, $contents))->withFileOverwrite($overwriteFile)->generate();

            $this->info("Created : {$path}");
        } catch (FileAlreadyExistException $e) {
            $this->components->error("File : {$path} already exists.");

            return E_ERROR;
        }

        return 0;
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return class_basename($this->argument($this->argumentName));
    }

    /**
     * Get default namespace.
     *
     * @return string
     */
    public function getDefaultNamespace(): string
    {
        return '';
    }

    /**
     * Get class namespace.
     *
     *
     * @param $module
     * @return string
     */
    public function getClassNamespace($module): string
    {
        $extra = str_replace($this->getClass(), '', $this->argument($this->argumentName));

        $extra = str_replace('/', '\\', $extra);

        $namespace = $this->laravel['modules']->config('namespace');

        $namespace .= '\\' . $module->getStudlyName();

        $namespace .= '\\' . $this->getDefaultNamespace();

        $namespace .= '\\' . $extra;

        $namespace = str_replace('/', '\\', $namespace);

        return trim($namespace, '\\');
    }
}
