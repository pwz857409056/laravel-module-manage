<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 模块命名空间
    |--------------------------------------------------------------------------
    |
    | 默认模块命名空间。
    |
    */
    'namespace' => 'Modules',

    'paths' => [
        /*
        |--------------------------------------------------------------------------
        | 模块路径
        |--------------------------------------------------------------------------
        |
        | 此路径用于保存生成的模块。还将添加此路径
        | 自动添加到扫描文件夹列表。
        |
        */
        'modules' => base_path('Modules'),
        /*
        |--------------------------------------------------------------------------
        | 生成器路径
        |--------------------------------------------------------------------------
        | 自定义生成文件夹的路径。
        | 将generate字段设置为false以不生成该文件夹
        */
        'generator' => [
            'config' => ['path' => 'Config', 'generate' => true],
            'model' => ['path' => 'Models', 'generate' => true],
            'routes' => ['path' => 'Routes', 'generate' => true],
            'controller' => ['path' => 'Http/Controllers', 'generate' => true],
            'filter' => ['path' => 'Http/Middleware', 'generate' => true],
            'request' => ['path' => 'Http/Requests', 'generate' => true],
            'provider' => ['path' => 'Providers', 'generate' => true],
        ],
    ],
    /*
    |--------------------------------------------------------------------------
    | 模块存根
    |--------------------------------------------------------------------------
    |
    | 默认模块存根。
    |
    */
    'stubs' => [
        'path' => dirname(__DIR__) . '/src/Commands/stubs',
        'files' => [
            'routes/api' => 'Routes/api.php',
            'scaffold/config' => 'Config/config.php',
        ],
        'replacements' => [
            'routes/api' => ['LOWER_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
        ],
        'gitkeep' => true,
    ],

];
