<?php

namespace Powitz\LaravelModuleManage\Contracts;

interface RunableInterface
{
    /**
     * 运行指定的命令
     *
     * @param string $command
     */
    public function run(string $command);
}
