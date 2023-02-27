<?php

namespace Powitz\LaravelModuleManage\Exceptions;

class InvalidAssetPath extends \Exception
{
    public static function missingModuleName($asset): static
    {
        return new static("Module name was not specified in asset [$asset].");
    }
}
