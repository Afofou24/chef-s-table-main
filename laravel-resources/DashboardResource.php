<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Create a new resource instance.
     */
    public function __construct(
        public array $stats,
        public array $recentOrders = [],
        public array $lowStockItems = [],
        public array $todayReservations = [],
        public array $salesChart = []
    ) {
        parent::__construct(null);
    }

    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'today_revenue' => $this->stats['today_revenue'] ?? 0,
                'today_orders' => $this->stats['today_orders'] ?? 0,
                'pending_orders' => $this->stats['pending_orders'] ?? 0,
                'active_tables' => $this->stats['active_tables'] ?? 0,
                'today_reservations' => $this->stats['today_reservations'] ?? 0,
                'low_stock_items' => $this->stats['low_stock_items'] ?? 0,
            ],
            'recent_orders' => OrderResource::collection($this->recentOrders),
            'low_stock_items' => StockItemResource::collection($this->lowStockItems),
            'today_reservations' => ReservationResource::collection($this->todayReservations),
            'sales_chart' => $this->salesChart,
        ];
    }
}
