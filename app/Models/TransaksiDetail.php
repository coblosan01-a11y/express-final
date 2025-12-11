<?php
// app/Models/TransaksiDetail.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiDetail extends Model
{
    use HasFactory;

    protected $table = 'transaksi_detail';

    protected $fillable = [
        'transaksi_id',
        'layanan_id',
        'layanan_nama',
        'kuantitas',
        'harga_satuan',
        'subtotal',
        'catatan'
    ];

    protected $casts = [
        'kuantitas' => 'array',
        'harga_satuan' => 'array',
        'subtotal' => 'decimal:2'
    ];

    // Relationships
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'layanan_id');
    }

    // Accessors
    public function getFormattedSubtotalAttribute()
    {
        return number_format($this->subtotal, 0, ',', '.');
    }

    public function getKuantitasTextAttribute()
    {
        if (!$this->kuantitas) return '';
        
        $text = [];
        foreach ($this->kuantitas as $satuan => $qty) {
            $text[] = "{$qty} {$satuan}";
        }
        
        return implode(', ', $text);
    }
}