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

    if (request()->has('seed')) {
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('migrate --force');
            \Illuminate\Support\Facades\Artisan::call('db:seed --force');
            return ['status' => 'success', 'message' => 'Cache cleared, database migrated and seeded successfully'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
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
            'env_vars_found' => collect($_ENV)->keys()->filter(function($key) {
                return str_contains($key, 'DB_') || str_contains($key, 'MYSQL');
            })->values(),
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
