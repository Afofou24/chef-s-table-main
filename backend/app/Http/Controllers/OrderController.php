<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\MenuItem;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['table', 'waiter', 'items.menuItem']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('order_type')) {
            $query->where('order_type', $request->input('order_type'));
        }

        if ($request->has('table_id')) {
            $query->where('table_id', $request->input('table_id'));
        }

        if ($request->has('waiter_id')) {
            $query->where('waiter_id', $request->input('waiter_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'order_type' => 'required|in:dine_in,takeaway,delivery',
            'guests_count' => 'integer|min:1|max:50',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:99',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'order_type.required' => 'Le type de commande est obligatoire.',
            'items.required' => 'Au moins un article est requis.',
            'items.min' => 'Au moins un article est requis.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'table_id' => $validated['table_id'] ?? null,
                'waiter_id' => $request->user()->id,
                'order_type' => $validated['order_type'],
                'guests_count' => $validated['guests_count'] ?? 1,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);
                
                $order->items()->create([
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $menuItem->price,
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                ]);

                $subtotal += $menuItem->price * $item['quantity'];
            }

            $taxRate = config('restaurant.tax_rate', 10) / 100;
            $taxAmount = $subtotal * $taxRate;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount,
            ]);

            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)->update(['status' => 'occupied']);
            }

            $order->load(['table', 'waiter', 'items.menuItem']);

            return response()->json([
                'message' => 'Commande créée avec succès.',
                'data' => $order,
            ], 201);
        });
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['table', 'waiter', 'items.menuItem', 'payments']);

        return response()->json($order);
    }

    /**
     * Update the specified order.
     */
    public function update(Request $request, Order $order): JsonResponse
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible de modifier une commande terminée ou annulée.',
            ], 422);
        }

        $validated = $request->validate([
            'table_id' => 'nullable|exists:restaurant_tables,id',
            'guests_count' => 'integer|min:1|max:50',
            'notes' => 'nullable|string|max:500',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $order->update($validated);

        if (isset($validated['discount_amount'])) {
            $order->update([
                'total_amount' => $order->subtotal + $order->tax_amount - $validated['discount_amount'],
            ]);
        }

        $order->load(['table', 'waiter', 'items.menuItem']);

        return response()->json([
            'message' => 'Commande mise à jour avec succès.',
            'data' => $order,
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,served,completed,cancelled',
        ]);

        $order->update(['status' => $validated['status']]);

        if ($validated['status'] === 'completed' && $order->table_id) {
            RestaurantTable::where('id', $order->table_id)->update(['status' => 'available']);
        }

        if ($validated['status'] === 'cancelled') {
            $order->items()->update(['status' => 'cancelled']);
            if ($order->table_id) {
                $hasOtherOrders = Order::where('table_id', $order->table_id)
                    ->where('id', '!=', $order->id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->exists();

                if (!$hasOtherOrders) {
                    RestaurantTable::where('id', $order->table_id)->update(['status' => 'available']);
                }
            }
        }

        return response()->json([
            'message' => 'Statut de la commande mis à jour.',
            'data' => $order->fresh(['table', 'waiter', 'items.menuItem']),
        ]);
    }

    /**
     * Add items to an existing order.
     */
    public function addItems(Request $request, Order $order): JsonResponse
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible d\'ajouter des articles à cette commande.',
            ], 422);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1|max:99',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $order) {
            $additionalSubtotal = 0;

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);

                $order->items()->create([
                    'menu_item_id' => $item['menu_item_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $menuItem->price,
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                ]);

                $additionalSubtotal += $menuItem->price * $item['quantity'];
            }

            $newSubtotal = $order->subtotal + $additionalSubtotal;
            $taxRate = config('restaurant.tax_rate', 10) / 100;
            $newTaxAmount = $newSubtotal * $taxRate;

            $order->update([
                'subtotal' => $newSubtotal,
                'tax_amount' => $newTaxAmount,
                'total_amount' => $newSubtotal + $newTaxAmount - $order->discount_amount,
            ]);

            return response()->json([
                'message' => 'Articles ajoutés avec succès.',
                'data' => $order->fresh(['table', 'waiter', 'items.menuItem']),
            ]);
        });
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): JsonResponse
    {
        if ($order->payments()->where('status', 'completed')->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une commande avec des paiements effectués.',
            ], 422);
        }

        if ($order->table_id) {
            $hasOtherOrders = Order::where('table_id', $order->table_id)
                ->where('id', '!=', $order->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();

            if (!$hasOtherOrders) {
                RestaurantTable::where('id', $order->table_id)->update(['status' => 'available']);
            }
        }

        $order->delete();

        return response()->json([
            'message' => 'Commande supprimée avec succès.',
        ]);
    }
}
