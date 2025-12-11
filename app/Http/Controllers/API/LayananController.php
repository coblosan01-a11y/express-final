<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use Illuminate\Http\Request;

class LayananController extends Controller
{
    /**
     * GET /api/layanan
     * Ambil semua data layanan.
     */
    public function index()
    {
        return Layanan::all();
    }

    /**
     * POST /api/layanan
     * Simpan layanan baru.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'nama' => 'required|string|max:255',
        'metode' => 'required|array|min:1',
        'hargaPerUnit' => 'required|array|min:1',
        'deskripsi' => 'nullable|string',
    ]);

    $layanan = Layanan::create([
        'nama' => $validated['nama'],
        'metode' => $validated['metode'],
        'hargaPerUnit' => $validated['hargaPerUnit'],
        'deskripsi' => $validated['deskripsi'] ?? null,
    ]);

    return response()->json([
        'message' => 'Layanan berhasil ditambahkan',
        'data' => $layanan
    ], 201);
}

    

    /**
     * PUT /api/layanan/{id}
     * Update layanan berdasarkan ID.
     */
    public function update(Request $request, $id)
    {
        $layanan = Layanan::findOrFail($id);
    
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'metode' => 'required|array',
            'hargaPerUnit' => 'required|array', // ✅ sama kayak store
            'deskripsi' => 'nullable|string',
        ]);
    
        $layanan->update([
            'nama' => $validated['nama'],
            'metode' => $validated['metode'],
            'hargaPerUnit' => $validated['hargaPerUnit'], // ✅ sama
            'deskripsi' => $validated['deskripsi'] ?? '',
        ]);
    
        return response()->json(['message' => 'Berhasil diperbarui']);
    }
    

    /**
     * DELETE /api/layanan/{id}
     * Hapus layanan berdasarkan ID.
     */
    public function destroy($id)
    {
        $layanan = Layanan::find($id);

        if (!$layanan) {
            return response()->json(['message' => 'Layanan tidak ditemukan'], 404);
        }

        $layanan->delete();
        
        return response()->json(['message' => 'Layanan berhasil dihapus']);
    }
}