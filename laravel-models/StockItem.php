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
        'description',
        'category',
        'quantity',
        'unit',
        'minimum_quantity',
        'cost_per_unit',
        'supplier',
        'last_restocked_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'minimum_quantity' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'last_restocked_at' => 'datetime',
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
        return $this->quantity <= $this->minimum_quantity;
    }

    /**
     * Scope for low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'minimum_quantity');
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Add stock
     */
    public function addStock(float $quantity, int $userId, ?string $notes = null): StockMovement
    {
        $this->quantity += $quantity;
        $this->last_restocked_at = now();
        $this->save();

        return $this->movements()->create([
            'type' => StockMovement::TYPE_IN,
            'quantity' => $quantity,
            'unit_cost' => $this->cost_per_unit,
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
            'unit_cost' => $this->cost_per_unit,
            'user_id' => $userId,
            'notes' => $notes,
        ]);
    }

    /**
     * Get total value
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->cost_per_unit;
    }
}
