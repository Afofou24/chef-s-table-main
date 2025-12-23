<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return 'OK';
});

Route::get('/debug', function () {
    // Force clear cache if requested or if we are stuck in cache
    $queryString = request()->getQueryString();
    if (str_contains($queryString, 'init') || str_contains($queryString, 'clear')) {
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            
            if (str_contains($queryString, 'init')) {
                \Illuminate\Support\Facades\Artisan::call('migrate --force');
                \Illuminate\Support\Facades\Artisan::call('db:seed --force');
                return [
                    'status' => 'success', 
                    'message' => 'Cache cleared and Database initialized successfully!',
                    'note' => 'If you still see 127.0.0.1 on the next page, your Railway variables are missing.'
                ];
            }
            return ['status' => 'success', 'message' => 'All caches cleared'];
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
            'database_config_cached' => [
                'host' => $dbConfig['host'] ?? 'N/A',
                'port' => $dbConfig['port'] ?? 'N/A',
                'database' => $dbConfig['database'] ?? 'N/A',
                'username' => $dbConfig['username'] ?? 'N/A',
            ],
            'raw_env_values' => [
                'DB_HOST' => $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? 'NOT FOUND',
                'MYSQLHOST' => $_ENV['MYSQLHOST'] ?? $_SERVER['MYSQLHOST'] ?? 'NOT FOUND',
                'DB_PORT' => $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? 'NOT FOUND',
                'MYSQLPORT' => $_ENV['MYSQLPORT'] ?? $_SERVER['MYSQLPORT'] ?? 'NOT FOUND',
            ],
            'db_error' => $dbError,
            'user_count' => $userCount,
            'tables' => $tables,
            'all_env_keys' => collect(array_merge(array_keys($_ENV), array_keys($_SERVER)))
                ->filter(fn($k) => str_contains($k, 'DB_') || str_contains($k, 'MYSQL'))
                ->unique()
                ->values(),
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
