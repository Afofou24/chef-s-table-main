<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return 'OK';
});

Route::get('/debug', function () {
    if (request()->has('clear')) {
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        return ['status' => 'success', 'message' => 'Cache cleared'];
    }

    try {
        $connection = config('database.default');
        $dbConfig = config("database.connections.$connection");
        
        $tables = [];
        $userCount = 0;
        $dbError = null;
        try {
            // Check connection by running a simple query
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $tables = \Illuminate\Support\Facades\DB::connection()->select('SHOW TABLES');
            $userCount = \Illuminate\Support\Facades\DB::table('users')->count();
        } catch (\Exception $e) {
            $dbError = $e->getMessage();
        }

        return [
            'status' => $dbError ? 'partial_error' : 'success',
            'current_connection' => $connection,
            'database_config' => [
                'host' => $dbConfig['host'] ?? 'N/A',
                'port' => $dbConfig['port'] ?? 'N/A',
                'database' => $dbConfig['database'] ?? 'N/A',
                'username' => $dbConfig['username'] ?? 'N/A',
            ],
            'db_error' => $dbError,
            'user_count' => $userCount,
            'tables' => $tables,
            'env_vars_check' => [
                'DB_CONNECTION' => env('DB_CONNECTION'),
                'DB_HOST' => env('DB_HOST'),
                'MYSQLHOST' => env('MYSQLHOST'),
                'DB_PORT' => env('DB_PORT'),
                'MYSQLPORT' => env('MYSQLPORT'),
            ],
            'is_config_cached' => app()->configurationIsCached(),
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'fatal_error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }
});
