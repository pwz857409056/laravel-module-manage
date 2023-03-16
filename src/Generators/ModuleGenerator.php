<?php

namespace Powitz\LaravelModuleManage\Generators;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command as Console;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Powitz\LaravelModuleManage\Module;
use Powitz\LaravelModuleManage\Support\Config\GenerateConfigReader;
use Powitz\LaravelModuleManage\Support\Stub;

class ModuleGenerator
{
    /**
     * The module name will create.
     *
     * @var string|null
     */
    protected ?string $name;

    /**
     * The laravel config instance.
     *
     * @var Config|null
     */
    protected ?Config $config;

    /**
     * The laravel filesystem instance.
     *
     * @var Filesystem|null
     */
    protected ?Filesystem $filesystem;

    /**
     * The laravel console instance.
     *
     * @var Console
     */
    protected Console $console;

    /**
     * The laravel component Factory instance.
     *
     * @var Factory
     */
    protected Factory $component;

    /**
     * The module instance.
     *
     * @var Module
     */
    protected Module $module;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of module will created. By default in studly case.
     *
     * @return string
     */
    public function getName(): string
    {
        return Str::studly($this->name);
    }

    /**
     * Get the laravel config instance.
     *
     * @return Config|null
     */
    public function getConfig(): ?Config
    {
        return $this->config;
    }

    /**
     * Set the laravel config instance.
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get the laravel filesystem instance.
     *
     * @return Filesystem|null
     */
    public function getFilesystem(): ?Filesystem
    {
        return $this->filesystem;
    }

    /**
     * Set the laravel filesystem instance.
     *
     * @param Filesystem $filesystem
     *
     * @return $this
     */
    public function setFilesystem(Filesystem $filesystem): static
    {
        $this->filesystem = $filesystem;

        return $this;
    }

    /**
     * Get the laravel console instance.
     *
     * @return Console
     */
    public function getConsole(): Console
    {
        return $this->console;
    }

    /**
     * Set the laravel console instance.
     *
     * @param Console $console
     *
     * @return $this
     */
    public function setConsole(Console $console): static
    {
        $this->console = $console;

        return $this;
    }

    /**
     * @return Factory
     */
    public function getComponent(): Factory
    {
        return $this->component;
    }

    /**
     * @param Factory $component
     * @return ModuleGenerator
     */
    public function setComponent(Factory $component): self
    {
        $this->component = $component;
        return $this;
    }

    /**
     * Get the module instance.
     *
     * @return Module
     */
    public function getModule(): Module
    {
        return $this->module;
    }

    /**
     * @desc:设置 module
     *
     * @param $module
     * @return $this
     */
    public function setModule($module): static
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Get the list of folders will created.
     *
     * @return array
     */
    public function getFolders(): array
    {
        return $this->module->config('paths.generator');
    }

    /**
     * Get the list of files will created.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->module->config('stubs.files');
    }

    public function generate(): int
    {
        $name = $this->getName();
        if (!$name) {
            $this->component->error("Module name cannot be empty");
            return E_ERROR;
        }
        if ($this->module->has($name)) {
            $this->component->error("Module [{$name}] already exists!");
            return E_ERROR;
        }
        $this->component->info("Creating module: [$name]");
        $this->generateFolders();

        $this->generateFiles();
        $this->generateResources();

        $this->console->newLine(1);
        $this->component->info("Module [{$name}] created successfully.");
        return 0;
    }

    /**
     * Generate the folders.
     */
    public function generateFolders(): void
    {
        foreach ($this->getFolders() as $key => $folder) {
            $folder = GenerateConfigReader::read($key);

            if ($folder->generate() === false) {
                continue;
            }
            $path = config('modules.paths.modules') . '/' . $this->getName() . '/' . $folder->getPath();
            $this->filesystem->makeDirectory($path, 0755, true);
            if (config('modules.stubs.gitkeep')) {
                $this->generateGitKeep($path);
            }
        }
    }

    /**
     * Generate git keep to the specified path.
     *
     * @param string $path
     */
    public function generateGitKeep(string $path): void
    {
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Generate the files.
     */
    public function generateFiles(): void
    {
        foreach ($this->getFiles() as $stub => $file) {
            $path = config('modules.paths.modules') . '/' . $this->getName() . '/' . $file;

            if (!$this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
            }

            $this->filesystem->put($path, $this->getStubContents($stub));

            $this->component->info("Created : {$path}");
        }
    }

    /**
     * Generate some resources.
     */
    public function generateResources(): void
    {
        if (GenerateConfigReader::read('provider')->generate() === true) {
            $this->console->call('module:make-provider', [
                'name' => $this->getName() . 'ServiceProvider',
                'module' => $this->getName(),
                '--master' => true,
            ]);
            $this->console->call('module:make-route-provider', [
                'module' => $this->getName(),
            ]);
        }
        if (GenerateConfigReader::read('response-enum')->generate() === true) {
            $this->console->call('module:make-response-enum', [
                'name' => 'response-enum',
                'module' => $this->getName(),
            ]);
            $this->console->call('module:make-base-service', [
                'name' => 'base-service',
                'module' => $this->getName(),
            ]);
        }
        $this->console->call('module:make-middleware', [
            'name' => 'accept-header',
            'module' => $this->getName(),
            '--master' => true,
        ]);
        $this->console->call('module:make-middleware', [
            'name' => 'enable-cross-request',
            'module' => $this->getName(),
            '--master' => true,
        ]);
        $this->console->call('module:make-controller', [
            'controller' => 'BaseController',
            'module' => $this->getName(),
            '--master' => true
        ]);
        $this->console->call('module:make-request', [
            'name' => 'form-request',
            'module' => $this->getName(),
            '--master' => true
        ]);
        $this->console->call('module:make-request', [
            'name' => 'scene-validator',
            'module' => $this->getName(),
            '--master' => true
        ]);
    }

    /**
     * Get the contents of the specified stub file by given stub name.
     *
     * @param $stub
     *
     * @return string
     */
    protected function getStubContents($stub): string
    {
        return (new Stub(
            '/' . $stub . '.stub',
            $this->getReplacement($stub)
        )
        )->render();
    }

    /**
     * Get array replacement for the specified stub.
     *
     * @param $stub
     *
     * @return array
     */
    protected function getReplacement($stub): array
    {
        $replacements = config('modules.stubs.replacements');

        if (!isset($replacements[$stub])) {
            return [];
        }

        $keys = $replacements[$stub];

        $replaces = [];

        if ($stub === 'json' || $stub === 'composer') {
            if (in_array('PROVIDER_NAMESPACE', $keys, true) === false) {
                $keys[] = 'PROVIDER_NAMESPACE';
            }
        }
        foreach ($keys as $key) {
            if (method_exists($this, $method = 'get' . ucfirst(Str::studly(strtolower($key))) . 'Replacement')) {
                $replaces[$key] = $this->$method();
            } else {
                $replaces[$key] = null;
            }
        }

        return $replaces;
    }

    /**
     * Get the module name in lower case.
     *
     * @return string
     */
    protected function getLowerNameReplacement(): string
    {
        return strtolower($this->getName());
    }

    /**
     * Get the module name in studly case.
     *
     * @return string
     */
    protected function getStudlyNameReplacement(): string
    {
        return $this->getName();
    }

    /**
     * Get replacement for $MODULE_NAMESPACE$.
     *
     * @return string
     */
    protected function getModuleNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', $this->module->config('namespace'));
    }

    protected function getProviderNamespaceReplacement(): string
    {
        return str_replace('\\', '\\\\', GenerateConfigReader::read('provider')->getNamespace());
    }
}
