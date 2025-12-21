<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Display a listing of settings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();

        if ($request->has('group')) {
            $query->where('group', $request->input('group'));
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        return response()->json($settings);
    }

    /**
     * Get settings grouped by category.
     */
    public function grouped(): JsonResponse
    {
        $settings = Setting::all()->groupBy('group');

        return response()->json($settings);
    }

    /**
     * Get a single setting by key.
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->firstOrFail();

        return response()->json($setting);
    }

    /**
     * Store a new setting.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:100|unique:settings,key|regex:/^[a-z_]+$/',
            'value' => 'nullable|string|max:5000',
            'group' => 'required|string|max:50',
            'type' => 'in:string,boolean,integer,json',
            'description' => 'nullable|string|max:255',
        ], [
            'key.required' => 'La clé est obligatoire.',
            'key.unique' => 'Cette clé existe déjà.',
            'key.regex' => 'La clé doit contenir uniquement des lettres minuscules et underscores.',
            'group.required' => 'Le groupe est obligatoire.',
        ]);

        $setting = Setting::create($validated);

        return response()->json([
            'message' => 'Paramètre créé avec succès.',
            'data' => $setting,
        ], 201);
    }

    /**
     * Update an existing setting.
     */
    public function update(Request $request, Setting $setting): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'nullable|string|max:5000',
            'description' => 'nullable|string|max:255',
        ]);

        $setting->update($validated);

        return response()->json([
            'message' => 'Paramètre mis à jour avec succès.',
            'data' => $setting,
        ]);
    }

    /**
     * Bulk update settings.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable|string|max:5000',
        ]);

        foreach ($validated['settings'] as $item) {
            Setting::where('key', $item['key'])->update(['value' => $item['value']]);
        }

        return response()->json([
            'message' => 'Paramètres mis à jour avec succès.',
        ]);
    }

    /**
     * Remove a setting.
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $setting->delete();

        return response()->json([
            'message' => 'Paramètre supprimé avec succès.',
        ]);
    }
}
