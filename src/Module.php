<?php

namespace Powitz\LaravelModuleManage;

use Illuminate\Support\Str;

class Module
{
    private ?string $moduleName;

    /**
     * @desc:设置模块名字
     *
     * @param $moduleName
     */
    public function setModuleName($moduleName): void
    {
        $this->moduleName = $moduleName;
    }

    /**
     * @desc:获取模块配置
     *
     * @param string $key
     * @param null $default
     * @return string|array|null
     */
    public function config(string $key, $default = null): array|string|null
    {
        return config('modules.' . $key, $default);
    }

    /**
     * @desc:获取小写名字
     *
     * @return string
     */
    public function getLowerName(): string
    {
        return strtolower($this->moduleName);
    }

    /**
     * @desc:方法将带有 _的字符串转换成驼峰命名的字符串
     *
     * @return string
     */
    public function getStudlyName(): string
    {
        return Str::studly($this->moduleName);
    }

    /**
     * @desc:获取模块路径
     *
     * @param $module
     * @return string
     */
    public function getModulePath($module): string
    {
        return $this->getPath() . '/' . Str::studly($module) . '/';
    }

    /**
     * @desc:获取模块根路径
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->config('paths.modules');
    }

    /**
     * @desc:判断模块是否存在
     *
     * @param $module
     * @return bool
     */
    public function has($module): bool
    {
        return file_exists($this->getModulePath($module));
    }

    public function __toString()
    {
        return $this->getStudlyName();
    }
}
