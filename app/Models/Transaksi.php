<?php
// app/Models/Transaksi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $fillable = [
        'kode_transaksi',
        'tanggal_transaksi',
        'customer_id',
        'customer_name',
        'customer_phone',
        'metode_pembayaran',
        'total_amount',
        'jumlah_bayar',
        'kembalian',
        'subtotal_layanan',
        'biaya_pickup',
        'status_transaksi',
        'status_cucian',
        'catatan',
        'created_by',
        
        // Pickup service fields
        'has_pickup_service',
        'pickup_setting_id',
        'pickup_service_name',
        'pickup_service_type',
        'pickup_jarak',
        'pickup_rentang',
        'pickup_date',
        'pickup_time',
        'pickup_special_instructions',
        'pickup_base_cost',
        'pickup_total_cost',
        'pickup_status',
        'pickup_logs',
        'last_pickup_update',
    ];

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
        'pickup_date' => 'date',
        'last_pickup_update' => 'datetime',
        'total_amount' => 'decimal:2',
        'jumlah_bayar' => 'decimal:2',
        'kembalian' => 'decimal:2',
        'subtotal_layanan' => 'decimal:2',
        'biaya_pickup' => 'decimal:2',
        'pickup_base_cost' => 'decimal:2',
        'pickup_total_cost' => 'decimal:2',
        'pickup_jarak' => 'decimal:1',
        'has_pickup_service' => 'boolean',
        'pickup_logs' => 'json',
    ];

    // Remove $dates - already handled by $casts
    // protected $dates = [
    //     'tanggal_transaksi',
    //     'pickup_date',
    //     'created_at',
    //     'updated_at'
    // ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'customer_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransaksiDetail::class, 'transaksi_id');
    }

    public function cashFlow(): HasOne
    {
        return $this->hasOne(CashFlow::class, 'transaksi_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Pickup Settings relationship (uncomment if pickup_settings table exists)
    // public function pickupSetting(): BelongsTo
    // {
    //     return $this->belongsTo(PickupSetting::class, 'pickup_setting_id');
    // }

    // Scopes
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_transaksi', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('metode_pembayaran', $method);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status_transaksi', $status);
    }

    public function scopeWithPickupService($query)
    {
        return $query->where('has_pickup_service', true);
    }

    public function scopeWithoutPickupService($query)
    {
        return $query->where('has_pickup_service', false);
    }

    public function scopeByCustomerPhone($query, $phone)
    {
        return $query->where('customer_phone', 'like', '%' . $phone . '%');
    }

    public function scopeByCustomerName($query, $name)
    {
        return $query->where('customer_name', 'like', '%' . $name . '%');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('tanggal_transaksi', Carbon::today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('tanggal_transaksi', Carbon::now()->month)
                    ->whereYear('tanggal_transaksi', Carbon::now()->year);
    }

    public function scopePaid($query)
    {
        return $query->where('status_transaksi', 'sukses');
    }

    public function scopePending($query)
    {
        return $query->where('status_transaksi', 'pending');
    }

    // Accessors
    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedSubtotalLayananAttribute()
    {
        return number_format($this->subtotal_layanan, 0, ',', '.');
    }

    public function getFormattedBiayaPickupAttribute()
    {
        return number_format($this->biaya_pickup, 0, ',', '.');
    }

    public function getFormattedKembalianAttribute()
    {
        return number_format($this->kembalian, 0, ',', '.');
    }

    public function getStatusTransaksiBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'sukses' => '<span class="badge badge-success">Lunas</span>',
            'cancelled' => '<span class="badge badge-danger">Dibatalkan</span>'
        ];

        return $badges[$this->status_transaksi] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    public function getStatusCucianBadgeAttribute()
    {
        $badges = [
            'pending' => '<span class="badge badge-warning">Menunggu</span>',
            'processing' => '<span class="badge badge-info">Proses</span>',
            'completed' => '<span class="badge badge-primary">Selesai</span>',
            'delivered' => '<span class="badge badge-success">Terkirim</span>'
        ];

        return $badges[$this->status_cucian] ?? '<span class="badge badge-secondary">Unknown</span>';
    }

    public function getMetodePembayaranTextAttribute()
    {
        $methods = [
            'tunai' => 'Tunai',
            'qris' => 'QRIS',
            'bayar-nanti' => 'Bayar Nanti'
        ];

        return $methods[$this->metode_pembayaran] ?? $this->metode_pembayaran;
    }

    public function getPickupServiceTypeTextAttribute()
    {
        $types = [
            'pickup_only' => 'Pickup Only',
            'pickup_delivery' => 'Pickup + Delivery'
        ];

        return $types[$this->pickup_service_type] ?? $this->pickup_service_type;
    }

    public function getFormattedPickupScheduleAttribute()
    {
        if (!$this->pickup_date || !$this->pickup_time) {
            return '-';
        }

        $date = Carbon::parse($this->pickup_date);
        return $date->format('d M Y') . ', ' . $this->pickup_time;
    }

    public function getPickupServiceSummaryAttribute()
    {
        if (!$this->has_pickup_service) {
            return null;
        }

        return [
            'service_name' => $this->pickup_service_name,
            'service_type' => $this->pickup_service_type,
            'jarak' => (float)$this->pickup_jarak,
            'rentang' => $this->pickup_rentang,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'special_instructions' => $this->pickup_special_instructions,
            'total_cost' => (float)$this->pickup_total_cost,
            'formatted_cost' => number_format($this->pickup_total_cost ?? 0, 0, ',', '.'),
            'formatted_schedule' => $this->formatted_pickup_schedule
        ];
    }

    // Mutators
    public function setTanggalTransaksiAttribute($value)
    {
        $this->attributes['tanggal_transaksi'] = Carbon::parse($value);
    }

    public function setPickupDateAttribute($value)
    {
        $this->attributes['pickup_date'] = $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }

    // Static Methods
    public static function generateKodeTransaksi($prefix = 'TRX')
    {
        $date = Carbon::now()->format('ymd');
        $lastTransaction = static::whereDate('created_at', Carbon::today())
            ->where('kode_transaksi', 'like', $prefix . $date . '%')
            ->orderBy('kode_transaksi', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->kode_transaksi, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $date . $newNumber;
    }

    public static function getTodaySummary()
    {
        $today = Carbon::today();

        return [
            'total_transactions' => static::whereDate('tanggal_transaksi', $today)->count(),
            'total_revenue' => static::whereDate('tanggal_transaksi', $today)
                ->where('status_transaksi', 'sukses')
                ->sum('total_amount'),
            'laundry_revenue' => static::whereDate('tanggal_transaksi', $today)
                ->where('status_transaksi', 'sukses')
                ->sum('subtotal_layanan'),
            'pickup_revenue' => static::whereDate('tanggal_transaksi', $today)
                ->where('status_transaksi', 'sukses')
                ->sum('biaya_pickup'),
            'pickup_count' => static::whereDate('tanggal_transaksi', $today)
                ->where('has_pickup_service', true)
                ->count(),
            'pending_payments' => static::whereDate('tanggal_transaksi', $today)
                ->where('status_transaksi', 'pending')
                ->count()
        ];
    }

    public static function getMonthlyStats($month = null, $year = null)
    {
        $month = $month ?? Carbon::now()->month;
        $year = $year ?? Carbon::now()->year;

        return static::whereMonth('tanggal_transaksi', $month)
            ->whereYear('tanggal_transaksi', $year)
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status_transaksi = "sukses" THEN total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status_transaksi = "sukses" THEN subtotal_layanan ELSE 0 END) as laundry_revenue,
                SUM(CASE WHEN status_transaksi = "sukses" THEN biaya_pickup ELSE 0 END) as pickup_revenue,
                COUNT(CASE WHEN has_pickup_service = 1 THEN 1 END) as pickup_transactions,
                AVG(CASE WHEN status_transaksi = "sukses" THEN total_amount END) as avg_transaction_value
            ')
            ->first();
    }

    // Event Hooks
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaksi) {
            if (empty($transaksi->kode_transaksi)) {
                $transaksi->kode_transaksi = static::generateKodeTransaksi();
            }

            // Auto-calculate total_amount if not set
            if (!$transaksi->total_amount) {
                $transaksi->total_amount = ($transaksi->subtotal_layanan ?? 0) + ($transaksi->biaya_pickup ?? 0);
            }

            // Set has_pickup_service based on pickup data
            if (!isset($transaksi->has_pickup_service)) {
                $transaksi->has_pickup_service = !empty($transaksi->pickup_service_name);
            }
        });

        static::updating(function ($transaksi) {
            // Recalculate total if subtotal components change
            if ($transaksi->isDirty(['subtotal_layanan', 'biaya_pickup'])) {
                $transaksi->total_amount = ($transaksi->subtotal_layanan ?? 0) + ($transaksi->biaya_pickup ?? 0);
            }
        });
    }

    // Helper Methods
    public function hasPickupService()
    {
        return $this->has_pickup_service && !empty($this->pickup_service_name);
    }

    public function hasLaundryItems()
    {
        return $this->details()->count() > 0;
    }

    public function isPickupOnly()
    {
        return $this->hasPickupService() && !$this->hasLaundryItems();
    }

    public function isLaundryOnly()
    {
        return $this->hasLaundryItems() && !$this->hasPickupService();
    }

    public function isCombinedService()
    {
        return $this->hasPickupService() && $this->hasLaundryItems();
    }

    public function isPaid()
    {
        return $this->status_transaksi === 'sukses';
    }

    public function isPending()
    {
        return $this->status_transaksi === 'pending';
    }

    public function isCancelled()
    {
        return $this->status_transaksi === 'cancelled';
    }

    public function canBeCancelled()
    {
        return in_array($this->status_cucian, ['pending', 'processing']) && 
               !$this->isCancelled();
    }

    public function getReceiptData()
    {
        return [
            'id' => $this->id,
            'kode_transaksi' => $this->kode_transaksi,
            'tanggal_transaksi' => $this->tanggal_transaksi->format('Y-m-d H:i:s'),
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'metode_pembayaran' => $this->metode_pembayaran,
            'total_amount' => (float)$this->total_amount,
            'subtotal_layanan' => (float)$this->subtotal_layanan,
            'biaya_pickup' => (float)$this->biaya_pickup,
            'jumlah_bayar' => (float)$this->jumlah_bayar,
            'kembalian' => (float)$this->kembalian,
            'status_transaksi' => $this->status_transaksi,
            'notes' => $this->catatan,
            'items' => $this->details->map(function ($detail) {
                return [
                    'layanan_nama' => $detail->layanan_nama,
                    'kuantitas' => $detail->kuantitas,
                    'harga_satuan' => $detail->harga_satuan,
                    'subtotal' => (float)$detail->subtotal,
                    'catatan' => $detail->catatan
                ];
            })->toArray(),
            'pickup_service' => $this->pickup_service_summary
        ];
    }
}