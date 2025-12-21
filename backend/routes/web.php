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
        $tables = [];
        try {
            $tables = \Illuminate\Support\Facades\DB::connection()->select('SHOW TABLES');
        } catch (\Exception $e) {
            $tables = "Error fetching tables: " . $e->getMessage();
        }

        return [
            'status' => 'success',
            'connection' => $connection,
            'database_name' => config("database.connections.$connection.database"),
            'env_db_connection' => env('DB_CONNECTION'),
            'env_db_host' => env('DB_HOST'),
            'tables' => $tables,
            'php_version' => PHP_VERSION,
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ];
    }
});
