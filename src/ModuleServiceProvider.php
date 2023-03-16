<?php

namespace Powitz\LaravelModuleManage;

use Illuminate\Support\ServiceProvider;
use Powitz\LaravelModuleManage\Commands;
use Powitz\LaravelModuleManage\Support\Stub;

/**
 * @desc:模块服务提供
 * @author: powitz<powitz@163.com>
 */
class ModuleServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            Commands\MakeModuleCommand::class,
            Commands\ProviderMakeCommand::class,
            Commands\CommandMakeCommand::class,
            Commands\RouteProviderMakeCommand::class,
            Commands\MiddlewareMakeCommand::class,
            Commands\ControllerMakeCommand::class,
            Commands\SceneValidatorMakeCommand::class,
            Commands\RequestMakeCommand::class,
            Commands\ResponseEnumMakeCommand::class,
            Commands\BaseServiceMakeCommand::class,
        ]);
        $this->publishes([
            dirname(__DIR__, 1) . '/config/config.php' => config_path('modules.php'),
        ], 'modules');
    }

    public function register()
    {
        $publishedConfig = config_path('modules.php');
        if (file_exists($publishedConfig)) {
            $this->mergeConfigFrom($publishedConfig, 'modules');
        } else {
            $this->mergeConfigFrom(dirname(__DIR__) . '/config/config.php', 'modules');
        }

        $path = config('modules.stubs.path') ?? __DIR__ . '/Commands/stubs';
        Stub::setBasePath($path);

        $this->app->singleton('modules', Module::class);
    }
}
