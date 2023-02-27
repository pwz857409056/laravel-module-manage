<?php

namespace Powitz\LaravelModuleManage\Process;


use Powitz\LaravelModuleManage\Contracts\RepositoryInterface;
use Powitz\LaravelModuleManage\Contracts\RunableInterface;

class Runner implements RunableInterface
{
    /**
     * The module instance.
     * @var RepositoryInterface
     */
    protected RepositoryInterface $module;

    public function __construct(RepositoryInterface $module)
    {
        $this->module = $module;
    }

    /**
     * Run the given command.
     *
     * @param string $command
     */
    public function run(string $command)
    {
        passthru($command);
    }
}
