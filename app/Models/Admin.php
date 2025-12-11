<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'telepon',
        'jabatan',
        'password',
        'status',
    ];

    // Scope untuk admin aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    // Method untuk cek apakah sudah ada pemilik (untuk setup awal)
    public static function hasPemilik()
    {
        return self::where('jabatan', 'pemilik')->count() > 0;
    }

    // Method untuk mendapatkan pemilik aktif
    public static function getPemilik()
    {
        return self::where('jabatan', 'pemilik')->where('status', 'aktif')->first();
    }

    // Method untuk cek apakah user adalah pemilik
    public function isPemilik()
    {
        return $this->jabatan === 'pemilik';
    }

    // Method untuk mendapatkan level akses (selalu full untuk pemilik)
    public function getAccessLevel()
    {
        return 'full'; // Pemilik selalu full access
    }
}