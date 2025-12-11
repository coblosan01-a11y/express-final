<?php
// app/Models/CashFlow.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashFlow extends Model
{
    use HasFactory;

    protected $table = 'cash_flow';

    protected $fillable = [
        'transaksi_id',
        'tanggal',
        'jenis_pembayaran',
        'amount',
        'tipe',
        'kategori',
        'deskripsi'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'amount' => 'decimal:2'
    ];

    // Relationships
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    // Scopes
    public function scopeIncome($query)
    {
        return $query->where('tipe', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('tipe', 'expense');
    }

    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('jenis_pembayaran', $method);
    }

    public function scopeByDateRange($query, $dateFrom, $dateTo)
    {
        return $query->whereBetween('tanggal', [$dateFrom, $dateTo]);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }

    public function getPaymentMethodTextAttribute()
    {
        $methods = [
            'tunai' => 'Tunai',
            'qris' => 'QRIS',
            'bayar-nanti' => 'Bayar Nanti'
        ];

        return $methods[$this->jenis_pembayaran] ?? $this->jenis_pembayaran;
    }

    public function getTipeTextAttribute()
    {
        $types = [
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran'
        ];

        return $types[$this->tipe] ?? $this->tipe;
    }
}