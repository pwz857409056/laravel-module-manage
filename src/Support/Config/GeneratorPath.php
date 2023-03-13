<?php

namespace Powitz\LaravelModuleManage\Support\Config;

class GeneratorPath
{
    private mixed $path;
    private mixed $generate;
    private mixed $namespace;

    public function __construct($config)
    {
        if (is_array($config)) {
            $this->path = $config['path'];
            $this->generate = $config['generate'];
            $this->namespace = $config['namespace'] ?? $this->convertPathToNamespace($config['path']);

            return;
        }
        $this->path = $config;
        $this->generate = (bool)$config;
        $this->namespace = $config;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function generate(): bool
    {
        return $this->generate;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    private function convertPathToNamespace($path): array|string
    {
        return str_replace('/', '\\', $path);
    }
}
