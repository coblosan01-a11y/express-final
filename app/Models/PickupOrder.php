<?php
// app/Models/PickupOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickupOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaksi_id',
        'pickup_code',
        'pickup_address',
        'delivery_address',
        'scheduled_pickup_time',
        'scheduled_delivery_time',
        'actual_pickup_time',
        'actual_delivery_time',
        'pickup_status',
        'pickup_notes',
        'delivery_notes',
        'pickup_lat',
        'pickup_lng',
        'delivery_lat',
        'delivery_lng',
    ];

    protected $casts = [
        'scheduled_pickup_time' => 'datetime',
        'scheduled_delivery_time' => 'datetime',
        'actual_pickup_time' => 'datetime',
        'actual_delivery_time' => 'datetime',
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'delivery_lat' => 'decimal:8',
        'delivery_lng' => 'decimal:8',
    ];

    // Relationship
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    // Generate pickup code
    public static function generatePickupCode(): string
    {
        $date = now()->format('Ymd');
        $prefix = "PKP{$date}";

        $lastOrder = self::where('pickup_code', 'like', "{$prefix}%")
            ->orderBy('pickup_code', 'desc')
            ->first();

        $nextNumber = $lastOrder
            ? intval(substr($lastOrder->pickup_code, -4)) + 1
            : 1;

        return "{$prefix}" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->pickup_code)) {
                $model->pickup_code = self::generatePickupCode();
            }
        });
    }
}