<?php

namespace Powitz\LaravelModuleManage\Support\Config;

use Powitz\LaravelModuleManage\Support\Config\GeneratorPath;

/**
 * 生成配置读取器
 */
class GenerateConfigReader
{
    public static function read(string $value): GeneratorPath
    {
        return new GeneratorPath(config("modules.paths.generator.$value"));
    }
}
