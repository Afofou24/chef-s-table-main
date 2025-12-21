<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockItemController extends Controller
{
    /**
     * Display a listing of stock items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockItem::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        if ($request->boolean('expiring_soon')) {
            $query->expiringSoon();
        }

        $stockItems = $query->orderBy('name')->paginate($request->input('per_page', 15));

        return response()->json($stockItems);
    }

    /**
     * Store a newly created stock item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'required|string|max:50|unique:stock_items,sku',
            'category' => 'nullable|string|max:50',
            'quantity' => 'required|numeric|min:0|max:999999.99',
            'unit' => 'required|string|max:20',
            'min_quantity' => 'numeric|min:0|max:999999.99',
            'unit_cost' => 'nullable|numeric|min:0|max:9999.99',
            'supplier' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'sku.required' => 'Le SKU est obligatoire.',
            'sku.unique' => 'Ce SKU existe déjà.',
            'quantity.required' => 'La quantité est obligatoire.',
            'unit.required' => 'L\'unité est obligatoire.',
            'expiry_date.after' => 'La date d\'expiration doit être future.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $stockItem = StockItem::create($validated);

            if ($validated['quantity'] > 0) {
                StockMovement::create([
                    'stock_item_id' => $stockItem->id,
                    'user_id' => $request->user()->id,
                    'type' => 'in',
                    'quantity' => $validated['quantity'],
                    'quantity_before' => 0,
                    'quantity_after' => $validated['quantity'],
                    'reason' => 'Stock initial',
                ]);
            }

            return response()->json([
                'message' => 'Article de stock créé avec succès.',
                'data' => $stockItem,
            ], 201);
        });
    }

    /**
     * Display the specified stock item.
     */
    public function show(StockItem $stockItem): JsonResponse
    {
        $stockItem->load(['movements' => function ($q) {
            $q->with('user:id,name')->orderBy('created_at', 'desc')->limit(20);
        }]);

        return response()->json($stockItem);
    }

    /**
     * Update the specified stock item.
     */
    public function update(Request $request, StockItem $stockItem): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => ['required', 'string', 'max:50', Rule::unique('stock_items')->ignore($stockItem->id)],
            'category' => 'nullable|string|max:50',
            'unit' => 'required|string|max:20',
            'min_quantity' => 'numeric|min:0|max:999999.99',
            'unit_cost' => 'nullable|numeric|min:0|max:9999.99',
            'supplier' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $stockItem->update($validated);

        return response()->json([
            'message' => 'Article de stock mis à jour avec succès.',
            'data' => $stockItem,
        ]);
    }

    /**
     * Adjust stock quantity.
     */
    public function adjustQuantity(Request $request, StockItem $stockItem): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:in,out,adjustment,loss,return',
            'quantity' => 'required|numeric|min:0.01|max:999999.99',
            'reason' => 'required|string|max:255',
            'reference' => 'nullable|string|max:100',
        ], [
            'type.required' => 'Le type de mouvement est obligatoire.',
            'quantity.required' => 'La quantité est obligatoire.',
            'reason.required' => 'La raison est obligatoire.',
        ]);

        $quantityBefore = $stockItem->quantity;

        if (in_array($validated['type'], ['out', 'loss'])) {
            if ($validated['quantity'] > $stockItem->quantity) {
                return response()->json([
                    'message' => 'Quantité insuffisante en stock.',
                ], 422);
            }
            $quantityAfter = $quantityBefore - $validated['quantity'];
        } elseif ($validated['type'] === 'adjustment') {
            $quantityAfter = $validated['quantity'];
            $validated['quantity'] = abs($quantityAfter - $quantityBefore);
        } else {
            $quantityAfter = $quantityBefore + $validated['quantity'];
        }

        return DB::transaction(function () use ($validated, $stockItem, $quantityBefore, $quantityAfter, $request) {
            $stockItem->update(['quantity' => $quantityAfter]);

            StockMovement::create([
                'stock_item_id' => $stockItem->id,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $validated['reason'],
                'reference' => $validated['reference'] ?? null,
            ]);

            return response()->json([
                'message' => 'Quantité ajustée avec succès.',
                'data' => $stockItem->fresh(),
            ]);
        });
    }

    /**
     * Get low stock items.
     */
    public function lowStock(): JsonResponse
    {
        $items = StockItem::lowStock()->orderBy('name')->get();

        return response()->json($items);
    }

    /**
     * Get expiring items.
     */
    public function expiringSoon(): JsonResponse
    {
        $items = StockItem::expiringSoon()->orderBy('expiry_date')->get();

        return response()->json($items);
    }

    /**
     * Remove the specified stock item.
     */
    public function destroy(StockItem $stockItem): JsonResponse
    {
        $stockItem->delete();

        return response()->json([
            'message' => 'Article de stock supprimé avec succès.',
        ]);
    }
}
