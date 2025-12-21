<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use HasFactory;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'number',
        'capacity',
        'location',
        'status',
        'notes',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_RESERVED = 'reserved';
    const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * Orders for this table
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    /**
     * Reservations for this table
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }

    /**
     * Current active order
     */
    public function currentOrder()
    {
        return $this->orders()
                    ->whereIn('status', ['pending', 'in_progress', 'ready'])
                    ->latest()
                    ->first();
    }

    /**
     * Scope for available tables
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Scope for free tables (alias)
     */
    public function scopeFree($query)
    {
        return $this->scopeAvailable($query);
    }

    /**
     * Check if table is available
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }
}
