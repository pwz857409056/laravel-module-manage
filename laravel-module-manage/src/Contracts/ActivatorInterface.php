<?php

namespace Powitz\LaravelModuleManage\Contracts;


use Powitz\LaravelModuleManage\Module;

interface ActivatorInterface
{
    /**
     * 启用模块
     *
     * @param Module $module
     */
    public function enable(Module $module): void;

    /**
     * 禁用模块
     *
     * @param Module $module
     */
    public function disable(Module $module): void;

    /**
     * 确定给定状态是否与模块状态相同。
     *
     * @param Module $module
     * @param bool $status
     *
     * @return bool
     */
    public function hasStatus(Module $module, bool $status): bool;

    /**
     * 为模块设置激活状态。
     *
     * @param Module $module
     * @param bool $active
     */
    public function setActive(Module $module, bool $active): void;

    /**
     * 通过模块名称设置模块状态
     *
     * @param string $name
     * @param bool $active
     */
    public function setActiveByName(string $name, bool $active): void;

    /**
     * 删除模块激活状态
     *
     * @param Module $module
     */
    public function delete(Module $module): void;

    /**
     * 删除由该类创建的任何模块激活状态
     */
    public function reset(): void;
}
