<?php

namespace Powitz\LaravelModuleManage;

use Illuminate\Support\Str;

class Module
{
    private ?string $moduleName;

    public function setModuleName($moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    public function config(string $key, $default = null)
    {
        return config('modules.' . $key, $default);
    }

    public function getLowerName(): string
    {
        return strtolower($this->moduleName);
    }

    public function getStudlyName(): string
    {
        return Str::studly($this->moduleName);
    }

    public function __toString()
    {
        return $this->getStudlyName();
    }

    public function getModulePath($module): string
    {
        return $this->getPath() . '/' . Str::studly($module) . '/';
    }

    public function getPath(): string
    {
        return $this->config('paths.modules');
    }

    public function isExist($module): bool
    {
        return file_exists($this->getModulePath($module));
    }
}
