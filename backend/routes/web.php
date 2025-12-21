<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return 'OK';
});

Route::get('/debug', function () {
    try {
        $connection = config('database.default');
        $dbConfig = config("database.connections.$connection");
        
        $tables = [];
        $dbError = null;
        try {
            // Check connection by running a simple query
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $tables = \Illuminate\Support\Facades\DB::connection()->select('SHOW TABLES');
        } catch (\Exception $e) {
            $dbError = $e->getMessage();
        }

        return [
            'status' => $dbError ? 'partial_error' : 'success',
            'current_connection' => $connection,
            'database_config' => [
                'host' => $dbConfig['host'] ?? 'N/A',
                'database' => $dbConfig['database'] ?? 'N/A',
                'username' => $dbConfig['username'] ?? 'N/A',
                // Don't show password for security
            ],
            'db_error' => $dbError,
            'tables' => $tables,
            'env_vars_check' => [
                'DB_CONNECTION' => env('DB_CONNECTION'),
                'DB_HOST' => env('DB_HOST'),
                'DB_DATABASE' => env('DB_DATABASE'),
            ],
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
