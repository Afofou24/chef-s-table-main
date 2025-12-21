<?php

namespace App\Http\Controllers;

use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class RestaurantTableController extends Controller
{
    /**
     * Display a listing of tables.
     */
    public function index(Request $request): JsonResponse
    {
        $query = RestaurantTable::query();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('location')) {
            $query->where('location', $request->input('location'));
        }

        if ($request->has('min_capacity')) {
            $query->where('capacity', '>=', $request->input('min_capacity'));
        }

        $tables = $query->withCount(['orders' => function ($q) {
            $q->whereIn('status', ['pending', 'confirmed', 'preparing', 'ready', 'served']);
        }])
        ->orderBy('number')
        ->paginate($request->input('per_page', 50));

        return response()->json($tables);
    }

    /**
     * Store a newly created table.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'number' => 'required|string|max:10|unique:restaurant_tables,number',
            'capacity' => 'required|integer|min:1|max:20',
            'location' => 'nullable|string|max:50|in:interieur,terrasse,prive',
            'status' => 'in:available,occupied,reserved,unavailable',
            'notes' => 'nullable|string|max:500',
        ], [
            'number.required' => 'Le numéro de table est obligatoire.',
            'number.unique' => 'Ce numéro de table existe déjà.',
            'capacity.required' => 'La capacité est obligatoire.',
            'capacity.min' => 'La capacité doit être d\'au moins 1 personne.',
            'location.in' => 'Emplacement invalide.',
        ]);

        $table = RestaurantTable::create($validated);

        return response()->json([
            'message' => 'Table créée avec succès.',
            'data' => $table,
        ], 201);
    }

    /**
     * Display the specified table.
     */
    public function show(RestaurantTable $restaurantTable): JsonResponse
    {
        $restaurantTable->load(['currentOrder.items.menuItem', 'reservations' => function ($q) {
            $q->where('reservation_date', '>=', now()->toDateString())
              ->orderBy('reservation_date')
              ->orderBy('reservation_time');
        }]);

        return response()->json($restaurantTable);
    }

    /**
     * Update the specified table.
     */
    public function update(Request $request, RestaurantTable $restaurantTable): JsonResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:10', Rule::unique('restaurant_tables')->ignore($restaurantTable->id)],
            'capacity' => 'required|integer|min:1|max:20',
            'location' => 'nullable|string|max:50|in:interieur,terrasse,prive',
            'status' => 'in:available,occupied,reserved,unavailable',
            'notes' => 'nullable|string|max:500',
        ]);

        $restaurantTable->update($validated);

        return response()->json([
            'message' => 'Table mise à jour avec succès.',
            'data' => $restaurantTable,
        ]);
    }

    /**
     * Remove the specified table.
     */
    public function destroy(RestaurantTable $restaurantTable): JsonResponse
    {
        if ($restaurantTable->orders()->whereNotIn('status', ['completed', 'cancelled'])->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette table car elle a des commandes en cours.',
            ], 422);
        }

        $restaurantTable->delete();

        return response()->json([
            'message' => 'Table supprimée avec succès.',
        ]);
    }

    /**
     * Update table status.
     */
    public function updateStatus(Request $request, RestaurantTable $restaurantTable): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,unavailable',
        ]);

        $restaurantTable->update($validated);

        return response()->json([
            'message' => 'Statut de la table mis à jour.',
            'data' => $restaurantTable,
        ]);
    }

    /**
     * Get available tables for a given capacity.
     */
    public function available(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'capacity' => 'required|integer|min:1',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
        ]);

        $query = RestaurantTable::available()
            ->where('capacity', '>=', $validated['capacity']);

        if (!empty($validated['date']) && !empty($validated['time'])) {
            $query->whereDoesntHave('reservations', function ($q) use ($validated) {
                $q->where('reservation_date', $validated['date'])
                  ->where('status', '!=', 'cancelled');
            });
        }

        $tables = $query->orderBy('capacity')->get();

        return response()->json($tables);
    }
}
