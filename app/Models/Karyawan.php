<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'telepon',
        'jabatan',
        'password',
        'status',
    ];

    // Scope untuk filter berdasarkan jabatan
    public function scopeByJabatan($query, $jabatan)
    {
        return $query->where('jabatan', $jabatan);
    }

    // Scope untuk karyawan aktif
    public function scopeActive($query)
    {
        return $query->where('status', 'aktif');
    }

    // Method untuk cek apakah karyawan adalah admin
    public function isAdmin()
    {
        return $this->jabatan === 'admin';
    }

    // Method untuk cek apakah karyawan adalah kasir
    public function isKasir()
    {
        return $this->jabatan === 'kasir';
    }

    // Method untuk cek apakah karyawan adalah kurir
    public function isKurir()
    {
        return $this->jabatan === 'kurir';
    }

    // Method untuk mendapatkan level akses
    public function getAccessLevel()
    {
        switch ($this->jabatan) {
            case 'admin':
                return 'high'; // Akses luas tapi tidak sebesar pemilik
            case 'kasir':
                return 'medium'; // Akses transaksi dan laporan harian
            case 'kurir':
                return 'low'; // Akses orderan saja
            default:
                return 'none';
        }
    }
}