<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'guests_count',
        'reservation_date',
        'reservation_time',
        'duration',
        'status',
        'notes',
    ];

    protected $casts = [
        'guests_count' => 'integer',
        'reservation_date' => 'date',
        'reservation_time' => 'string',
        'duration' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_NO_SHOW = 'no_show';

    /**
     * Table for this reservation
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    /**
     * Scope for today's reservations
     */
    public function scopeToday($query)
    {
        return $query->whereDate('reservation_date', today());
    }

    /**
     * Scope for upcoming reservations
     */
    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', today())
                     ->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_COMPLETED => 'Terminée',
            self::STATUS_NO_SHOW => 'Non présenté',
            default => $this->status,
        };
    }

    /**
     * Get formatted datetime
     */
    public function getFormattedDateTimeAttribute(): string
    {
        return $this->reservation_date->format('d/m/Y') . ' à ' . $this->reservation_time;
    }

    /**
     * Alias for guests_count (backward compatibility)
     */
    public function getPartySizeAttribute(): int
    {
        return $this->guests_count;
    }

    /**
     * Check if reservation can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }
}
