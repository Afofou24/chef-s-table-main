<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    /**
     * Display a listing of backups.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Backup::with('createdBy:id,first_name,last_name');

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $backups = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($backups);
    }

    /**
     * Create a new backup.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $filename = 'backup_' . now()->format('Y-m-d_H-i-s') . '.sql';
        $path = 'backups/' . $filename;

        $backup = Backup::create([
            'filename' => $filename,
            'path' => $path,
            'size' => 0,
            'type' => 'manual',
            'status' => 'pending',
            'created_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        try {
            // PHP-native backup implementation (works without mysqldump binary)
            // PHP-native backup implementation
            // Force use of env variables to bypass potential caching/config mismatches
            $dbHost = env('MYSQLHOST', env('DB_HOST', '127.0.0.1'));
            $dbName = env('MYSQLDATABASE', env('DB_DATABASE', 'laravel'));
            $dbUser = env('MYSQLUSER', env('DB_USERNAME', 'root'));
            $dbPass = env('MYSQLPASSWORD', env('DB_PASSWORD', ''));
            $dbPort = env('MYSQLPORT', env('DB_PORT', 3306));

            Storage::disk('local')->makeDirectory('backups');
            $absolutePath = Storage::disk('local')->path($path);

            $dumpSettings = [
                'compress' => 'None',
                'no-data' => false,
                'add-drop-table' => true,
                'single-transaction' => true,
                'lock-tables' => false,
                'add-locks' => true,
                'extended-insert' => true,
                'disable-keys' => true,
                'skip-triggers' => false,
                'add-drop-trigger' => true,
                'routines' => true,
                'databases' => false,
                'add-drop-database' => false,
                'hex-blob' => true,
                'no-create-info' => false,
                'where' => ''
            ];

            $dumper = new \Ifsnop\Mysqldump\Mysqldump(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}",
                $dbUser,
                $dbPass,
                $dumpSettings
            );

            $dumper->start($absolutePath);

            if (!file_exists($absolutePath) || filesize($absolutePath) === 0) {
                throw new \Exception("Backup file was not created or is empty.");
            }

            $backup->update([
                'status' => 'completed',
                'size' => Storage::disk('local')->size($path),
            ]);

            return response()->json([
                'message' => 'Sauvegarde créée avec succès.',
                'data' => $backup->fresh('createdBy:id,first_name,last_name'),
            ], 201);

        } catch (\Exception $e) {
            $backup->update(['status' => 'failed']);
            \Illuminate\Support\Facades\Log::error('Backup failed: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Erreur lors de la création de la sauvegarde.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified backup.
     */
    public function show(Backup $backup): JsonResponse
    {
        $backup->load('createdBy:id,first_name,last_name');

        return response()->json($backup);
    }

    /**
     * Download a backup file.
     */
    public function download(Backup $backup)
    {
        if ($backup->status !== 'completed') {
            return response()->json([
                'message' => 'Cette sauvegarde n\'est pas disponible.',
            ], 422);
        }

        if (!Storage::disk('local')->exists($backup->path)) {
            return response()->json([
                'message' => 'Fichier de sauvegarde introuvable.',
            ], 404);
        }

        return Storage::disk('local')->download($backup->path, $backup->filename);
    }

    /**
     * Restore from a backup.
     */
    public function restore(Request $request, Backup $backup): JsonResponse
    {
        if ($backup->status !== 'completed') {
            return response()->json([
                'message' => 'Cette sauvegarde ne peut pas être restaurée.',
            ], 422);
        }

        if (!Storage::disk('local')->exists($backup->path)) {
            return response()->json([
                'message' => 'Fichier de sauvegarde introuvable.',
            ], 404);
        }

        // Real restore implementation using mysql binary and Symfony Process
        $mysqlPath = config('backup.mysql_path');
        $dbHost = config('database.connections.mysql.host');
        if ($dbHost === '127.0.0.1')
            $dbHost = 'localhost';
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        $env = array_merge($_SERVER, [
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\Windows',
            'SystemDrive' => getenv('SystemDrive') ?: 'C:',
            'TEMP' => getenv('TEMP'),
            'TMP' => getenv('TMP'),
            'PATH' => getenv('PATH'),
        ]);

        $absolutePath = Storage::disk('local')->path($backup->path);

        // Build command
        $command = [
            $mysqlPath,
            '--user=' . $dbUser,
            '--password=' . $dbPass,
            '--host=' . $dbHost,
            $dbName
        ];

        $process = new Process($command, null, $env);
        $process->setInput(file_get_contents($absolutePath));
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'message' => 'Erreur lors de la restauration.',
                'error' => "mysql failed (code " . $process->getExitCode() . "): " . $process->getErrorOutput(),
            ], 500);
        }

        return response()->json([
            'message' => 'Restauration effectuée avec succès.',
        ]);
    }

    /**
     * Remove the specified backup.
     */
    public function destroy(Backup $backup): JsonResponse
    {
        if (Storage::disk('local')->exists($backup->path)) {
            Storage::disk('local')->delete($backup->path);
        }

        $backup->delete();

        return response()->json([
            'message' => 'Sauvegarde supprimée avec succès.',
        ]);
    }

    /**
     * Clean old backups.
     */
    public function cleanOld(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
        ]);

        $cutoffDate = now()->subDays($validated['days']);

        $oldBackups = Backup::where('created_at', '<', $cutoffDate)
            ->where('type', 'automatic')
            ->get();

        $deletedCount = 0;

        foreach ($oldBackups as $backup) {
            if (Storage::disk('local')->exists($backup->path)) {
                Storage::disk('local')->delete($backup->path);
            }
            $backup->delete();
            $deletedCount++;
        }

        return response()->json([
            'message' => "{$deletedCount} sauvegarde(s) supprimée(s).",
        ]);
    }
}
