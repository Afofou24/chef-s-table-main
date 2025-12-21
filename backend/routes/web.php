<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return 'OK';
});

Route::get('/debug', function () {
    return [
        'connection' => config('database.default'),
        'mysql_config' => [
            'host' => config('database.connections.mysql.host'),
            'database' => config('database.connections.mysql.database'),
            'username' => config('database.connections.mysql.username'),
        ],
        'env_db_host' => env('DB_HOST'),
        'tables' => \Illuminate\Support\Facades\DB::connection()->select('SHOW TABLES'),
    ];
});
