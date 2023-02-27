<?php

namespace Powitz\LaravelModuleManage;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Powitz\LaravelModuleManage\Contracts\RepositoryInterface;
use Powitz\LaravelModuleManage\Exceptions\InvalidActivatorClass;
use Powitz\LaravelModuleManage\Providers\BootstrapServiceProvider;
use Powitz\LaravelModuleManage\Providers\ConsoleServiceProvider;
use Powitz\LaravelModuleManage\Providers\ContractsServiceProvider;
use Powitz\LaravelModuleManage\Support\Stub;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * 别名
     *
     * @var array
     */
    protected array $alias = [
        'modules' => Contracts\RepositoryInterface::class
    ];

    /**
     * 服务提供者
     *
     * @var array
     */
    protected array $providers = [
        ConsoleServiceProvider::class,
        ContractsServiceProvider::class,
    ];

    public function boot()
    {
        $this->app->register(BootstrapServiceProvider::class);
        $this->app->singleton('modules', Contracts\RepositoryInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerBind();
        $this->registerAlias();
        $this->registerProviders();
        $this->registerPublishing();
        $this->setupStubPath();

    }

    /**
     * 配置
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'modules');
    }

    /**
     * 别名
     *
     * @return void
     */
    protected function registerAlias(): void
    {
        foreach ($this->alias as $alias => $class) {
            AliasLoader::getInstance()->alias($alias, $class);
        }
    }

    /**
     * 服务提供者
     *
     * @return void
     */
    protected function registerProviders(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * 绑定
     *
     * @return void
     */
    protected function registerBind(): void
    {
        $this->app->singleton(Contracts\RepositoryInterface::class, function ($app) {
            $path = $app['config']->get('modules.paths.modules');
            return new Laravel\LaravelFileRepository($app, $path);
        });
        $this->app->singleton(Contracts\ActivatorInterface::class, function ($app) {
            $activator = $app['config']->get('modules.activator');
            $class = $app['config']->get('modules.activators.' . $activator)['class'];
            if ($class === null) {
                throw InvalidActivatorClass::missingConfig();
            }
            return new $class($app);
        });
    }

    /**
     * Setup stub path.
     */
    protected function setupStubPath()
    {
        $path = $this->app['config']->get('modules.stubs.path') ?? __DIR__ . '/Commands/stubs';
        Stub::setBasePath($path);
        $this->app->booted(function ($app) {
            /** @var RepositoryInterface $moduleRepository */
            $moduleRepository = $app[RepositoryInterface::class];
            if ($moduleRepository->config('stubs.enabled') === true) {
                Stub::setBasePath($moduleRepository->config('stubs.path'));
            }
        });
    }

    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $configPath = __DIR__ . '/../config/config.php';
            $stubsPath = dirname(__DIR__) . '/src/Commands/stubs';

            $this->publishes([
                $configPath => config_path('modules.php'),
            ], 'config');

            $this->publishes([
                $stubsPath => base_path('stubs/powitz-stubs'),
            ], 'stubs');
        }
    }
}
