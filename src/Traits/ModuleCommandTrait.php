<?php

namespace Powitz\LaravelModuleManage\Traits;

trait ModuleCommandTrait
{
    /**
     * Get the module name.
     *
     * @return string
     */
    public function getModuleName(): string
    {
        return app('modules')->getStudlyName();
    }
}
