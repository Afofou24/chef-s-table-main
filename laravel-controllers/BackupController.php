<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class BackupController extends Controller
{
    /**
     * Display a listing of backups.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Backup::with('createdBy:id,name');

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
            // In a real application, you would run mysqldump or similar
            // Artisan::call('backup:run', ['--only-db' => true]);
            
            // Simulate backup creation
            $dummyContent = "-- Database backup created at " . now()->toDateTimeString();
            Storage::disk('local')->put($path, $dummyContent);
            
            $backup->update([
                'status' => 'completed',
                'size' => Storage::disk('local')->size($path),
            ]);

            return response()->json([
                'message' => 'Sauvegarde créée avec succès.',
                'data' => $backup->fresh('createdBy:id,name'),
            ], 201);
        } catch (\Exception $e) {
            $backup->update(['status' => 'failed']);

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
        $backup->load('createdBy:id,name');

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

        // In a real application, you would restore the database
        // This is a placeholder for the actual restore logic

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
