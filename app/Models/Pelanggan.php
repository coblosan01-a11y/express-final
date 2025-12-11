<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pelanggan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pelanggans';

    protected $fillable = [
        'nama_pelanggan',
        'telepon',
        'kode_pelanggan',
    ];

    /**
     * Generate kode pelanggan baru.
     *
     * @return string
     */
    public static function generateKode()
    {
        $latest = self::orderBy('id', 'DESC')->first();
        $newId = $latest ? $latest->id + 1 : 1;
        return 'PLG-' . str_pad($newId, 4, '0', STR_PAD_LEFT);
    }
}