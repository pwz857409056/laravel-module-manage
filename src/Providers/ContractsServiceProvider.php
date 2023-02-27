<?php

namespace Powitz\LaravelModuleManage\Providers;

use Illuminate\Support\ServiceProvider;
use Powitz\LaravelModuleManage\Contracts\RepositoryInterface;
use Powitz\LaravelModuleManage\Laravel\LaravelFileRepository;

class ContractsServiceProvider extends ServiceProvider
{
    /**
     * Register some binding.
     */
    public function register()
    {
        $this->app->bind(RepositoryInterface::class, LaravelFileRepository::class);
    }
}
