<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get revenue distribution by category.
     */
    public function revenueByCategory(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        
        $revenue = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->select('categories.name', DB::raw('SUM(order_items.quantity * order_items.unit_price) as value'))
            ->groupBy('categories.name', 'categories.id')
            ->get();

        return response()->json($revenue);
    }

    /**
     * Get popular items by volume.
     */
    public function popularItems(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        $limit = $request->input('limit', 5);

        $items = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', now()->subDays($days))
            ->select('menu_items.name', DB::raw('SUM(order_items.quantity) as orders'))
            ->groupBy('menu_items.name', 'menu_items.id')
            ->orderBy('orders', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($items);
    }
}
