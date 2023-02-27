<?php

namespace Powitz\LaravelModuleManage\Laravel;


use Powitz\LaravelModuleManage\FileRepository;

class LaravelFileRepository extends FileRepository
{
    /**
     * {@inheritdoc}
     */
    protected function createModule(...$args)
    {
        return new Module(...$args);
    }
}
