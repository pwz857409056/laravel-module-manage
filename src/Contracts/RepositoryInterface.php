<?php

namespace Powitz\LaravelModuleManage\Contracts;


use Illuminate\Filesystem\Filesystem;
use Powitz\LaravelModuleManage\Collection;
use Powitz\LaravelModuleManage\Exceptions\ModuleNotFoundException;
use Powitz\LaravelModuleManage\Module;

interface RepositoryInterface
{
    /**
     * 获取所有模块
     *
     */
    public function all();

    /**
     * 获取缓存模块
     *
     */
    public function getCached();

    /**
     * 扫描并获取所有可用模块
     *
     */
    public function scan();

    /**
     * 获取模块作为模块集合实例
     *
     */
    public function toCollection();

    /**
     * 获取扫描路径
     *
     */
    public function getScanPaths();

    /**
     * 获取启用模块的列表
     *
     */
    public function allEnabled();

    /**
     * 获取禁用模块列表
     *
     */
    public function allDisabled();

    /**
     * 从所有模块获取计数
     *
     */
    public function count();

    /**
     * 获取所有有序模块
     */
    public function getOrdered($direction = 'asc');

    /**
     * 通过给定的状态获取模块
     *
     */
    public function getByStatus($status);

    /**
     * 查找特定模块
     *
     */
    public function find(string $name);

    /**
     * 找到特定的模块。如果有返回，否则抛出异常
     *
     */
    public function findOrFail(string $name);

    /**
     * 根据模块名字获取模块路径
     */
    public function getModulePath($moduleName);

    public function getFiles();

    /**
     * 从配置文件中获取特定的配置数据
     */
    public function config(string $key, $default = null);

    /**
     * 获取模块路径
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * 引导模块
     */
    public function boot(): void;

    /**
     * 注册模块
     */
    public function register(): void;

    /**
     * 获取特定模块的资源路径
     */
    public function assetPath(string $module): string;

    /**
     * 删除特定模块
     */
    public function delete(string $module): bool;

    /**
     * 确定给定模块是否已激活
     */
    public function isEnabled(string $name): bool;

    /**
     * 确定给定模块是否未激活
     */
    public function isDisabled(string $name): bool;
}
