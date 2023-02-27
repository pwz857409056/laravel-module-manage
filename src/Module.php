<?php

namespace Powitz\LaravelModuleManage;

use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Translation\Translator;
use Powitz\LaravelModuleManage\Contracts\ActivatorInterface;

abstract class Module
{
    use Macroable;

    /**
     * 应用程序实例
     *
     * @var Container
     */
    protected $app;

    /**
     * 模块名称
     *
     * @var string
     */
    protected $name;

    /**
     * 模块路径
     *
     * @var string
     */
    protected $path;

    /**
     * 缓存Json对象的var数组，按文件名键入
     *
     * @var array
     */
    protected $moduleJson = [];
    /**
     * @var CacheManager
     */
    private $cache;
    /**
     * @var Filesystem
     */
    private $files;
    /**
     * @var Translator
     */
    private $translator;
    /**
     * @var ActivatorInterface
     */
    private $activator;

    public function json($file = null): Json
    {
        if ($file === null) {
            $file = 'module.json';
        }

        return Arr::get($this->moduleJson, $file, function () use ($file) {
            return $this->moduleJson[$file] = new Json($this->getPath() . '/' . $file, $this->files);
        });
    }

    public function __construct(Container $app, string $name, $path)
    {
        $this->name = $name;
        $this->path = $path;
        $this->cache = $app['cache'];
        $this->files = $app['files'];
        $this->translator = $app['translator'];
        $this->activator = $app[ActivatorInterface::class];
        $this->app = $app;
    }

    /**
     * @desc:获取模块名
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @desc:获取小写的模块名
     *
     * @return string
     */
    public function getLowerName(): string
    {
        return strtolower($this->name);
    }

    /**
     * @desc:获取 studlycase 惯例的模块名称
     *
     * @return string
     */
    public function getStudlyName(): string
    {
        return Str::studly($this->name);
    }

    public function getSnakeName(): string
    {
        return Str::snake($this->name);
    }

    public function getDescription(): string
    {
        return $this->get('description');
    }

    public function getPriority(): string
    {
        return $this->get('priority');
    }

    /**
     * @desc:获取模块路径
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath($path): Module
    {
        $this->path = $path;

        return $this;
    }

    public function boot(): void
    {
        if (config('modules.register.translations', true) === true) {
            $this->registerTranslation();
        }

        if ($this->isLoadFilesOnBoot()) {
            $this->registerFiles();
        }

        $this->fireEvent('boot');
    }

    protected function registerTranslation(): void
    {
        $lowerName = $this->getLowerName();

        $langPath = $this->getPath() . '/Resources/lang';

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $lowerName);
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->json()->get($key, $default);
    }

    public function getComposerAttr($key, $default = null)
    {
        return $this->json('composer.json')->get($key, $default);
    }

    public function register(): void
    {
        $this->registerAliases();

        $this->registerProviders();

        if ($this->isLoadFilesOnBoot() === false) {
            $this->registerFiles();
        }

        $this->fireEvent('register');
    }

    protected function fireEvent($event): void
    {
        $this->app['events']->dispatch(sprintf('modules.%s.' . $event, $this->getLowerName()), [$this]);
    }

    abstract public function registerAliases(): void;


    abstract public function registerProviders(): void;


    abstract public function getCachedServicesPath(): string;


    protected function registerFiles(): void
    {
        foreach ($this->get('files', []) as $file) {
            include $this->path . '/' . $file;
        }
    }


    public function __toString()
    {
        return $this->getStudlyName();
    }


    public function isStatus(bool $status): bool
    {
        return $this->activator->hasStatus($this, $status);
    }

    /**
     * @desc:检查是否启用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->activator->hasStatus($this, true);
    }

    /**
     * @desc:检查是否禁用
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }


    public function setActive(bool $active): void
    {
        $this->activator->setActive($this, $active);
    }

    /**
     * @desc:禁用指定的模块
     *
     */
    public function disable(): void
    {
        $this->fireEvent('disabling');

        $this->activator->disable($this);
        $this->flushCache();

        $this->fireEvent('disabled');
    }

    /**
     * @desc:启用指定的模块
     *
     */
    public function enable(): void
    {
        $this->fireEvent('enabling');

        $this->activator->enable($this);
        $this->flushCache();

        $this->fireEvent('enabled');
    }

    /**
     * @desc:删除指定模块
     *
     * @return bool
     */
    public function delete(): bool
    {
        $this->activator->delete($this);

        return $this->json()->getFilesystem()->deleteDirectory($this->getPath());
    }

    /**
     * @desc:获取额外的路径
     *
     * @param string $path
     * @return string
     */
    public function getExtraPath(string $path): string
    {
        return $this->getPath() . '/' . $path;
    }


    protected function isLoadFilesOnBoot(): bool
    {
        return config('modules.register.files', 'register') === 'boot' &&
            // force register method if option == boot && app is AsgardCms
            !class_exists('\Modules\Core\Foundation\AsgardCms');
    }

    private function flushCache(): void
    {
        if (config('modules.cache.enabled')) {
            $this->cache->store(config('modules.cache.driver'))->flush();
        }
    }


    private function loadTranslationsFrom(string $path, string $namespace): void
    {
        $this->translator->addNamespace($namespace, $path);
    }
}
