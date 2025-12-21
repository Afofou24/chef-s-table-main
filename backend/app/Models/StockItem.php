<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sku',
        'category',
        'quantity',
        'unit',
        'min_quantity',
        'unit_cost',
        'supplier',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Stock movements for this item
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity;
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('min_quantity')
                     ->whereRaw('quantity <= min_quantity');
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for expiring items
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereDate('expiry_date', '<=', now()->addDays($days))
                     ->whereDate('expiry_date', '>=', now());
    }

    /**
     * Add stock
     */
    public function addStock(float $quantity, int $userId, ?string $notes = null): StockMovement
    {
        $this->quantity += $quantity;
        $this->save();

        return $this->movements()->create([
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'unit_cost' => $this->unit_cost,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Remove stock
     */
    public function removeStock(float $quantity, int $userId, ?string $notes = null): StockMovement
    {
        $this->quantity -= $quantity;
        $this->save();

        return $this->movements()->create([
            'type' => StockMovement::TYPE_OUT,
            'quantity' => $quantity,
            'unit_cost' => $this->unit_cost,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Get total value
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->unit_cost;
    }
}
