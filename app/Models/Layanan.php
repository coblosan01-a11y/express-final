<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
use HasFactory;

protected $fillable = [
  'nama',
  'metode',
  'hargaPerUnit',
  'deskripsi',
];

protected $casts = [
  'metode' => 'array',
  'hargaPerUnit' => 'array',
];
}