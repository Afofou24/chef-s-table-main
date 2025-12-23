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
    $queryString = (string)request()->getQueryString();
    if (str_contains($queryString, 'init') || str_contains($queryString, 'clear')) {
        try {
            // Aggressive clearing
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            \Illuminate\Support\Facades\Artisan::call('view:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            
            // Manual file deletion if Artisan fails in some environments
            $configCache = base_path('bootstrap/cache/config.php');
            if (file_exists($configCache)) {
                @unlink($configCache);
            }

            if (str_contains($queryString, 'init')) {
                \Illuminate\Support\Facades\Artisan::call('migrate:fresh --force');
                \Illuminate\Support\Facades\Artisan::call('db:seed --force');
                return [
                    'status' => 'success', 
                    'message' => 'AGGRESSIVE CLEAR DONE. Database initialized.',
                    'config_cache_file_exists' => file_exists($configCache)
                ];
            }
            return ['status' => 'success', 'message' => 'All caches aggressively cleared'];
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
            'keys_check' => [
                'config_app_key_snippet' => substr(config('app.key'), 0, 15) . '...',
                'env_app_key_snippet' => substr(getenv('APP_KEY'), 0, 15) . '...',
            ],
            'current_connection' => $connection,
            'user_count' => $userCount,
            'user_emails' => \Illuminate\Support\Facades\DB::table('users')->pluck('email'),
            'database_config_active' => [
                'host' => config('database.connections.mysql.host'),
                'database' => config('database.connections.mysql.database'),
            ],
            'raw_env_values' => [
                'DB_HOST' => getenv('DB_HOST') ?: 'NOT FOUND',
                'MYSQLHOST' => getenv('MYSQLHOST') ?: 'NOT FOUND',
                'DB_URL_SET' => !empty(getenv('DB_URL')),
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
