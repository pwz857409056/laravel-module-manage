<?php

namespace Powitz\LaravelModuleManage;

use Illuminate\Support\ServiceProvider;
use Powitz\LaravelModuleManage\Commands;
use Powitz\LaravelModuleManage\Support\Stub;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            Commands\MakeModuleCommand::class,
            Commands\ProviderMakeCommand::class,
            Commands\CommandMakeCommand::class,
            Commands\RouteProviderMakeCommand::class,
        ]);
        $this->publishes([
            dirname(__DIR__, 1) . '/config/config.php' => config_path('module.php'),
        ], 'module');
    }

    public function register()
    {
        $publishedConfig = config_path('module.php');
        if (file_exists($publishedConfig)) {
            $this->mergeConfigFrom($publishedConfig, 'modules');
        } else {
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/config.php', 'modules');
        }

        $path = config('modules.stubs.path') ?? __DIR__ . '/Commands/stubs';
        Stub::setBasePath($path);

        $this->app->singleton(Module::class);
        $this->app->alias(Module::class, 'modules');
    }
}
