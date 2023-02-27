<?php

namespace Powitz\LaravelModuleManage;

use Countable;
use Illuminate\Cache\CacheManager;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Powitz\LaravelModuleManage\Contracts\RepositoryInterface;
use Powitz\LaravelModuleManage\Exceptions\InvalidAssetPath;
use Powitz\LaravelModuleManage\Exceptions\ModuleNotFoundException;
use Powitz\LaravelModuleManage\Process\Installer;
use Powitz\LaravelModuleManage\Process\Updater;
use Symfony\Component\Process\Process;

abstract class FileRepository implements RepositoryInterface, Countable
{
    use Macroable;

    /**
     *
     * Application instance
     */
    protected $app;

    /**
     * The module path.
     *
     * @var string|null
     */
    protected ?string $path;

    /**
     * The scanned paths.
     *
     * @var array
     */
    protected array $paths = [];

    /**
     * @var string
     */
    protected string $stubPath;
    /**
     * @var UrlGenerator
     */
    private mixed $url;
    /**
     * @var ConfigRepository
     */
    private mixed $config;
    /**
     * @var Filesystem
     */
    private mixed $files;
    /**
     * @var CacheManager
     */
    private mixed $cache;

    /**
     * The constructor.
     * @param Container $app
     * @param string|null $path
     */
    public function __construct(Container $app, string $path = null)
    {
        $this->app = $app;
        $this->path = $path;
        $this->url = $app['url'];
        $this->config = $app['config'];
        $this->files = $app['files'];
        $this->cache = $app['cache'];
    }

    /**
     * Add other module location.
     *
     * @param string $path
     *
     * @return $this
     */
    public function addLocation(string $path): static
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Get all additional paths.
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * 获取扫描路径
     *
     * @return array
     */
    public function getScanPaths(): array
    {
        $paths = $this->paths;

        $paths[] = $this->getPath();

        if ($this->config('scan.enabled')) {
            $paths = array_merge($paths, $this->config('scan.paths'));
        }

        return array_map(function ($path) {
            return Str::endsWith($path, '/*') ? $path : Str::finish($path, '/*');
        }, $paths);
    }

    /**
     * Creates a new Module instance
     *
     * @param mixed ...$args
     * @return Module
     */
    abstract protected function createModule(...$args);

    /**
     * 获取扫描的模块
     *
     * @return array
     * @throws \Exception
     */
    public function scan(): array
    {
        $paths = $this->getScanPaths();

        $modules = [];

        foreach ($paths as $key => $path) {
            $manifests = $this->getFiles()->glob("{$path}/module.json");

            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $name = Json::make($manifest)->get('name');

                $modules[$name] = $this->createModule($this->app, $name, dirname($manifest));
            }
        }

        return $modules;
    }

    /**
     * 获取所有模块
     *
     * @return array
     * @throws \Exception
     */
    public function all(): array
    {
        if (!$this->config('cache.enabled')) {
            return $this->scan();
        }

        return $this->formatCached($this->getCached());
    }

    /**
     * Format the cached data as array of modules.
     *
     * @param array $cached
     *
     * @return array
     */
    protected function formatCached(array $cached): array
    {
        $modules = [];

        foreach ($cached as $name => $module) {
            $path = $module['path'];

            $modules[$name] = $this->createModule($this->app, $name, $path);
        }

        return $modules;
    }

    /**
     * 获取所有缓存的模块
     *
     * @return array
     */
    public function getCached(): array
    {
        return $this->cache->store($this->config->get('modules.cache.driver'))->remember($this->config('cache.key'), $this->config('cache.lifetime'), function () {
            return $this->toCollection()->toArray();
        });
    }

    /**
     * 以集合实例的形式获取所有模块
     *
     * @return Collection
     * @throws \Exception
     */
    public function toCollection(): Collection
    {
        return new Collection($this->scan());
    }

    /**
     * 根据状态获取模块。1 表示激活，0 表示不激活。
     *
     * @param $status
     *
     * @return array
     * @throws \Exception
     */
    public function getByStatus($status): array
    {
        $modules = [];

        /** @var Module $module */
        foreach ($this->all() as $name => $module) {
            if ($module->isStatus($status)) {
                $modules[$name] = $module;
            }
        }

        return $modules;
    }

    /**
     * 检查指定的模块。如果它存在，将返回 true，否则返回 false
     *
     * @param $name
     *
     * @return bool
     * @throws \Exception
     */
    public function has($name): bool
    {
        return array_key_exists($name, $this->all());
    }

    /**
     * 获取所有启用的模块
     *
     * @return array
     * @throws \Exception
     */
    public function allEnabled(): array
    {
        return $this->getByStatus(true);
    }

    /**
     * 获取所有禁用的模块
     *
     * @return array
     * @throws \Exception
     */
    public function allDisabled(): array
    {
        return $this->getByStatus(false);
    }

    /**
     * 获取所有模块的计数
     *
     * @return int
     * @throws \Exception
     */
    public function count(): int
    {
        return count($this->all());
    }

    /**
     * 获取有序的模块
     *
     * @param string $direction
     *
     * @return array
     * @throws \Exception
     */
    public function getOrdered($direction = 'asc'): array
    {
        $modules = $this->allEnabled();

        uasort($modules, function (Module $a, Module $b) use ($direction) {
            if ($a->get('priority') === $b->get('priority')) {
                return 0;
            }

            if ($direction === 'desc') {
                return $a->get('priority') < $b->get('priority') ? 1 : -1;
            }

            return $a->get('priority') > $b->get('priority') ? 1 : -1;
        });

        return $modules;
    }

    /**
     * 获取模块路径
     */
    public function getPath(): string
    {
        return $this->path ?: $this->config('paths.modules', base_path('Modules'));
    }

    /**
     * 注册模块
     */
    public function register(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->register();
        }
    }

    /**
     * 启动所有可用模块。
     */
    public function boot(): void
    {
        foreach ($this->getOrdered() as $module) {
            $module->boot();
        }
    }

    /**
     * 找到一个特定的模块
     *
     * @throws \Exception
     */
    public function find(string $name)
    {
        foreach ($this->all() as $module) {
            if ($module->getLowerName() === strtolower($name)) {
                return $module;
            }
        }

        return;
    }

    /**
     * 找到一个模块，如果有，返回 module 实例，否则抛出 Powitz\LaravelModuleManage\Exceptions
     *
     * @param string $name
     *
     * @return Module
     *
     * @throws ModuleNotFoundException
     * @throws \Exception
     */
    public function findOrFail(string $name)
    {
        $module = $this->find($name);
        if ($module !== null) {
            return $module;
        }

        throw new ModuleNotFoundException("Module [{$name}] does not exist!");
    }

    /**
     * 获取所有启用的模块作为集合实例
     *
     * @param int $status
     *
     * @return Collection
     * @throws \Exception
     */
    public function collections(int $status = 1): Collection
    {
        return new Collection($this->getByStatus($status));
    }

    /**
     * 获取指定模块的模块路径
     *
     * @param $module
     *
     * @return string
     * @throws \Exception
     */
    public function getModulePath($module): string
    {
        try {
            return $this->findOrFail($module)->getPath() . '/';
        } catch (ModuleNotFoundException $e) {
            return $this->getPath() . '/' . Str::studly($module) . '/';
        }
    }

    /**
     * 从指定模块中获取资源路径。
     *
     * @param string $module
     * @return string
     */
    public function assetPath(string $module): string
    {
        return $this->config('paths.assets') . '/' . $module;
    }

    /**
     * 从这个包中获取 config 值。
     *
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        return $this->config->get('modules.' . $key, $default);
    }

    /**
     * 获取使用的存储路径
     *
     * @return string
     */
    public function getUsedStoragePath(): string
    {
        $directory = storage_path('app/modules');
        if ($this->getFiles()->exists($directory) === false) {
            $this->getFiles()->makeDirectory($directory, 0777, true);
        }

        $path = storage_path('app/modules/modules.used');
        if (!$this->getFiles()->exists($path)) {
            $this->getFiles()->put($path, '');
        }

        return $path;
    }

    /**
     * 在 cli session 中设置使用的模块
     *
     * @param $name
     *
     * @throws ModuleNotFoundException
     */
    public function setUsed($name): void
    {
        $module = $this->findOrFail($name);

        $this->getFiles()->put($this->getUsedStoragePath(), $module);
    }

    /**
     * Forget the module used for cli session.
     */
    public function forgetUsed(): void
    {
        if ($this->getFiles()->exists($this->getUsedStoragePath())) {
            $this->getFiles()->delete($this->getUsedStoragePath());
        }
    }

    /**
     * 从 cli session 中获取使用的模块
     * @return string
     * @throws ModuleNotFoundException
     * @throws FileNotFoundException
     */
    public function getUsedNow(): string
    {
        return $this->findOrFail($this->getFiles()->get($this->getUsedStoragePath()));
    }

    /**
     * Get laravel filesystem instance.
     *
     * @return Filesystem
     */
    public function getFiles(): Filesystem
    {
        return $this->files;
    }

    /**
     * 获取模块的资源路径
     *
     * @return string
     */
    public function getAssetsPath(): string
    {
        return $this->config('paths.assets');
    }

    /**
     * 从特定模块获取资源 url
     * @param string $asset
     * @return string
     * @throws InvalidAssetPath
     */
    public function asset($asset): string
    {
        if (Str::contains($asset, ':') === false) {
            throw InvalidAssetPath::missingModuleName($asset);
        }
        list($name, $url) = explode(':', $asset);

        $baseUrl = str_replace(public_path() . DIRECTORY_SEPARATOR, '', $this->getAssetsPath());

        $url = $this->url->asset($baseUrl . "/{$name}/" . $url);

        return str_replace(['http://', 'https://'], '//', $url);
    }

    /**
     * 检查模块是否启用
     *
     * @throws ModuleNotFoundException
     */
    public function isEnabled(string $name): bool
    {
        return $this->findOrFail($name)->isEnabled();
    }

    /**
     *检查模块是否被禁用
     *
     * @throws ModuleNotFoundException
     */
    public function isDisabled(string $name): bool
    {
        return !$this->isEnabled($name);
    }

    /**
     * 启用模块
     *
     * @param string $name
     * @return void
     * @throws ModuleNotFoundException
     */
    public function enable(string $name): void
    {
        $this->findOrFail($name)->enable();
    }

    /**
     * 禁用模块
     *
     * @param string $name
     * @return void
     * @throws ModuleNotFoundException
     */
    public function disable(string $name): void
    {
        $this->findOrFail($name)->disable();
    }

    /**
     * 删除模块
     *
     * @throws ModuleNotFoundException
     */
    public function delete(string $name): bool
    {
        return $this->findOrFail($name)->delete();
    }

    /**
     * 更新指定模块的依赖项。
     *
     * @param string $module
     */
    public function update(string $module): void
    {
        with(new Updater($this))->update($module);
    }

    /**
     * 根据给定的模块名称安装指定的模块
     *
     * @param string $name
     * @param string $version
     * @param string $type
     * @param bool $subtree
     *
     * @return Process
     */
    public function install(string $name, string $version = 'dev-master', string $type = 'composer', bool $subtree = false)
    {
        $installer = new Installer($name, $version, $type, $subtree);

        return $installer->run();
    }

    /**
     * Get stub path.
     *
     * @return string|null
     */
    public function getStubPath()
    {
        if ($this->stubPath !== null) {
            return $this->stubPath;
        }

        if ($this->config('stubs.enabled') === true) {
            return $this->config('stubs.path');
        }

        return $this->stubPath;
    }

    /**
     * Set stub path.
     *
     * @param string $stubPath
     *
     * @return $this
     */
    public function setStubPath($stubPath)
    {
        $this->stubPath = $stubPath;

        return $this;
    }
}
