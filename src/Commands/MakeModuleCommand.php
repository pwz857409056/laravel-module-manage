<?php

namespace Powitz\LaravelModuleManage\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Powitz\LaravelModuleManage\Module;
use Powitz\LaravelModuleManage\Support\Config\GenerateConfigReader;
use Powitz\LaravelModuleManage\Support\Stub;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;


class MakeModuleCommand extends Command
{
    /**
     * The laravel filesystem instance.
     *
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * The module instance.
     *
     * @var Module
     */
    protected Module $module;

    /**
     * @var string|null
     */
    private ?string $moduleName;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'module:make {name?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->moduleName = $this->argument('name');
        $this->filesystem = $this->laravel['files'];
        $this->module = $this->laravel['modules'];
        $this->module->setModuleName($this->moduleName);
    }

    /**
     * Execute the console command.
     *
     * @return void
     * @throws Exception|Throwable
     */
    public function handle(): void
    {
        if (empty($this->moduleName)) {
            $this->components->error("argument name is empty");
            return;
        }
        if ($this->module->isExist($this->moduleName)) {
            $this->components->error("Module already exists");
            return;
        }
        $this->generateFolders();
        $this->generateFiles();
        $this->generateResources();

        $this->info("Package [{$this->moduleName}] created successfully");
    }

    /**
     * Get the name of module will created. By default in studly case.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return Str::studly($this->moduleName);
    }

    /**
     * Get the list of folders will created.
     *
     * @return array
     */
    public function getFolders(): array
    {
        return config('modules.paths.generator');
    }

    /**
     * Generate the folders.
     */
    public function generateFolders()
    {
        foreach ($this->getFolders() as $key => $folder) {
            $folder = GenerateConfigReader::read($key);

            if ($folder->generate() === false) {
                continue;
            }
            $path = config('modules.paths.modules') . '/' . $this->getModuleName() . '/' . $folder->getPath();
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
    public function generateGitKeep(string $path)
    {
        $this->filesystem->put($path . '/.gitkeep', '');
    }

    /**
     * Get the list of files will created.
     *
     * @return array
     */
    public function getFiles(): array
    {
        return config('modules.stubs.files');
    }

    /**
     * Generate the files.
     */
    public function generateFiles()
    {
        foreach ($this->getFiles() as $stub => $file) {
            $path = config('modules.paths.modules') . '/' . $this->getModuleName() . '/' . $file;

            if (!$this->filesystem->isDirectory($dir = dirname($path))) {
                $this->filesystem->makeDirectory($dir, 0775, true);
            }

            $this->filesystem->put($path, $this->getStubContents($stub));

            $this->info("Created : {$path}");
        }
    }

    /**
     * Generate some resources.
     */
    public function generateResources()
    {
        if (GenerateConfigReader::read('provider')->generate() === true) {
            $this->call('module:make-provider', [
                'name' => $this->getModuleName() . 'ServiceProvider',
                'module' => $this->getModuleName(),
                '--master' => true,
            ]);
            $this->call('module:make-route-provider', [
                'module' => $this->getModuleName(),
            ]);
        }
        $this->call('module:make-accept-header', [
            'name' => 'AcceptHeader',
            'module' => $this->getModuleName(),
        ]);
        $this->call('module:enable-cross-request', [
            'name' => 'EnableCrossRequest',
            'module' => $this->getModuleName(),
        ]);
        $this->call('module:make-controller', [
            'controller' => 'BaseController',
            'module' => $this->getModuleName(),
            '--base' => true
        ]);
        $this->call('module:make-form-request', [
            'name' => 'FormRequest',
            'module' => $this->getModuleName(),
        ]);
        $this->call('module:make-scene-validator', [
            'name' => 'SceneValidator',
            'module' => $this->getModuleName(),
        ]);
    }

    /**
     * Get class name.
     *
     * @return string
     */
    public function getClass(): string
    {
        return class_basename($this->argument('name'));
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
        return strtolower($this->getModuleName());
    }

    /**
     * Get the module name in studly case.
     *
     * @return string
     */
    protected function getStudlyNameReplacement(): string
    {
        return $this->getModuleName();
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
