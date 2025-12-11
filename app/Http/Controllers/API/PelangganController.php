<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    /**
     * Tampilkan semua pelanggan.
     */
    public function index()
    {
        $pelanggans = Pelanggan::all();

        return response()->json([
            'message' => 'Daftar pelanggan.',
            'data' => $pelanggans,
        ]);
    }

    /**
     * Simpan pelanggan baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'telepon' => [
                'required',
                'regex:/^08[0-9]{7,}$/',
                'unique:pelanggans,telepon',
            ],
        ]);
    
        // Tambahkan kode pelanggan secara manual
        $validated['kode_pelanggan'] = Pelanggan::generateKode();
    
        $pelanggan = Pelanggan::create($validated);
    
        return response()->json([
            'message' => 'Pelanggan berhasil ditambahkan!',
            'data' => $pelanggan,
        ], 201);
    }
    


    /**
     * Tampilkan detail 1 pelanggan.
     */
    public function show($id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        return response()->json([
            'message' => 'Detail pelanggan.',
            'data' => $pelanggan,
        ]);
    }

    /**
     * Update pelanggan.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'telepon' => ['required', 'regex:/^08[0-9]{7,}$/'],
        ]);

        $pelanggan = Pelanggan::findOrFail($id);

        $pelanggan->update([
            'nama_pelanggan' => $request->nama_pelanggan,
            'telepon' => $request->telepon,
        ]);

        return response()->json([
            'message' => 'Pelanggan berhasil diperbarui!',
            'data' => $pelanggan,
        ]);
    }

    /**
     * Hapus pelanggan.
     */
    public function destroy($id)
{
    $pelanggan = Pelanggan::findOrFail($id);
    $pelanggan->forceDelete(); // Hapus permanen 

    return response()->json([
        'message' => 'Pelanggan berhasil dihapus!',
    ]);
}

}