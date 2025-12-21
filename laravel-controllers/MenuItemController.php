<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * Display a listing of menu items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MenuItem::with('category');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('is_available')) {
            $query->where('is_available', $request->boolean('is_available'));
        }

        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }

        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        $menuItems = $query->orderBy('name')->paginate($request->input('per_page', 15));

        return response()->json($menuItems);
    }

    /**
     * Store a newly created menu item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'preparation_time' => 'nullable|integer|min:1|max:180',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'allergens' => 'nullable|string|max:255',
            'calories' => 'nullable|integer|min:0|max:9999',
        ], [
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'Catégorie invalide.',
            'name.required' => 'Le nom du plat est obligatoire.',
            'price.required' => 'Le prix est obligatoire.',
            'price.min' => 'Le prix doit être positif.',
            'image.max' => 'L\'image ne peut pas dépasser 2 Mo.',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('menu-items', 'public');
        }

        $menuItem = MenuItem::create($validated);
        $menuItem->load('category');

        return response()->json([
            'message' => 'Plat créé avec succès.',
            'data' => $menuItem,
        ], 201);
    }

    /**
     * Display the specified menu item.
     */
    public function show(MenuItem $menuItem): JsonResponse
    {
        $menuItem->load('category');

        return response()->json($menuItem);
    }

    /**
     * Update the specified menu item.
     */
    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:9999.99',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'preparation_time' => 'nullable|integer|min:1|max:180',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'allergens' => 'nullable|string|max:255',
            'calories' => 'nullable|integer|min:0|max:9999',
        ]);

        if ($request->hasFile('image')) {
            if ($menuItem->image) {
                Storage::disk('public')->delete($menuItem->image);
            }
            $validated['image'] = $request->file('image')->store('menu-items', 'public');
        }

        $menuItem->update($validated);
        $menuItem->load('category');

        return response()->json([
            'message' => 'Plat mis à jour avec succès.',
            'data' => $menuItem,
        ]);
    }

    /**
     * Remove the specified menu item.
     */
    public function destroy(MenuItem $menuItem): JsonResponse
    {
        if ($menuItem->orderItems()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce plat car il a été commandé.',
            ], 422);
        }

        if ($menuItem->image) {
            Storage::disk('public')->delete($menuItem->image);
        }

        $menuItem->delete();

        return response()->json([
            'message' => 'Plat supprimé avec succès.',
        ]);
    }

    /**
     * Toggle availability status.
     */
    public function toggleAvailability(MenuItem $menuItem): JsonResponse
    {
        $menuItem->update(['is_available' => !$menuItem->is_available]);

        return response()->json([
            'message' => $menuItem->is_available ? 'Plat disponible.' : 'Plat indisponible.',
            'data' => $menuItem,
        ]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(MenuItem $menuItem): JsonResponse
    {
        $menuItem->update(['is_featured' => !$menuItem->is_featured]);

        return response()->json([
            'message' => $menuItem->is_featured ? 'Plat mis en avant.' : 'Plat retiré de la mise en avant.',
            'data' => $menuItem,
        ]);
    }
}
