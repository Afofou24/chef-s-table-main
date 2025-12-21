<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Display a listing of order items (for kitchen).
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderItem::with(['order.table', 'menuItem']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('order_id')) {
            $query->where('order_id', $request->input('order_id'));
        }

        $items = $query->orderBy('created_at')->paginate($request->input('per_page', 50));

        return response()->json($items);
    }

    /**
     * Get items for kitchen display.
     */
    public function kitchen(Request $request): JsonResponse
    {
        $items = OrderItem::with(['order.table', 'menuItem'])
            ->whereIn('status', ['pending', 'preparing'])
            ->whereHas('order', function ($q) {
                $q->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->orderBy('created_at')
            ->get()
            ->groupBy('order_id');

        return response()->json($items);
    }

    /**
     * Update item status.
     */
    public function updateStatus(Request $request, OrderItem $orderItem): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,served,cancelled',
        ]);

        $orderItem->update(['status' => $validated['status']]);

        $this->updateOrderStatusBasedOnItems($orderItem->order);

        return response()->json([
            'message' => 'Statut de l\'article mis à jour.',
            'data' => $orderItem->fresh(['order', 'menuItem']),
        ]);
    }

    /**
     * Update the specified order item.
     */
    public function update(Request $request, OrderItem $orderItem): JsonResponse
    {
        if (in_array($orderItem->order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible de modifier un article d\'une commande terminée.',
            ], 422);
        }

        $validated = $request->validate([
            'quantity' => 'integer|min:1|max:99',
            'notes' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated, $orderItem) {
            $oldQuantity = $orderItem->quantity;
            $orderItem->update($validated);

            if (isset($validated['quantity']) && $validated['quantity'] !== $oldQuantity) {
                $this->recalculateOrderTotals($orderItem->order);
            }

            return response()->json([
                'message' => 'Article mis à jour avec succès.',
                'data' => $orderItem->fresh(['order', 'menuItem']),
            ]);
        });
    }

    /**
     * Remove the specified order item.
     */
    public function destroy(OrderItem $orderItem): JsonResponse
    {
        if (in_array($orderItem->order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Impossible de supprimer un article d\'une commande terminée.',
            ], 422);
        }

        $order = $orderItem->order;
        $orderItem->delete();

        $this->recalculateOrderTotals($order);

        if ($order->items()->count() === 0) {
            $order->delete();
            return response()->json([
                'message' => 'Article et commande vide supprimés.',
            ]);
        }

        return response()->json([
            'message' => 'Article supprimé avec succès.',
        ]);
    }

    /**
     * Recalculate order totals.
     */
    private function recalculateOrderTotals(Order $order): void
    {
        $subtotal = $order->items()->sum(DB::raw('quantity * unit_price'));
        $taxRate = config('restaurant.tax_rate', 10) / 100;
        $taxAmount = $subtotal * $taxRate;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $subtotal + $taxAmount - $order->discount_amount,
        ]);
    }

    /**
     * Update order status based on item statuses.
     */
    private function updateOrderStatusBasedOnItems(Order $order): void
    {
        $order->refresh();
        $items = $order->items;

        if ($items->where('status', '!=', 'cancelled')->isEmpty()) {
            return;
        }

        $activeItems = $items->where('status', '!=', 'cancelled');

        if ($activeItems->every(fn($item) => $item->status === 'served')) {
            $order->update(['status' => 'served']);
        } elseif ($activeItems->every(fn($item) => in_array($item->status, ['ready', 'served']))) {
            $order->update(['status' => 'ready']);
        } elseif ($activeItems->contains(fn($item) => $item->status === 'preparing')) {
            $order->update(['status' => 'preparing']);
        }
    }
}
