<?php

namespace Powitz\LaravelModuleManage\Commands;

use Exception;
use Illuminate\Console\Command;
use Powitz\LaravelModuleManage\Generators\ModuleGenerator;
use Throwable;


class MakeModuleCommand extends Command
{
    /**
     * 命令名称.
     *
     * @var string
     */
    protected $signature = 'module:make {name?}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = 'Create a new module';

    /**
     * 执行命令
     *
     * @return void
     * @throws Exception|Throwable
     */
    public function handle(): void
    {
        $name = $this->argument('name');
        with(new ModuleGenerator($name))
            ->setFilesystem($this->laravel['files'])
            ->setModule($this->laravel['modules'])
            ->setConfig($this->laravel['config'])
            ->setConsole($this)
            ->setComponent($this->components)
            ->generate();
    }
}
