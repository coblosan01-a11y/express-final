<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KaryawanController extends Controller
{
    // INDEX - Ambil semua data karyawan (admin, kasir, kurir)
    public function index()
    {
        try {
            $karyawans = Karyawan::all();
            Log::info('KaryawanController index: Found ' . $karyawans->count() . ' karyawans');
            return response()->json($karyawans);
        } catch (\Exception $e) {
            Log::error('KaryawanController index error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data karyawan'], 500);
        }
    }

    // INDEX by Jabatan - Filter karyawan berdasarkan jabatan
    public function getByJabatan($jabatan)
    {
        try {
            $karyawans = Karyawan::byJabatan($jabatan)->get();
            return response()->json($karyawans);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data karyawan'], 500);
        }
    }

    // STORE - Tambah karyawan (admin, kasir, kurir)
    public function store(Request $request)
    {
        Log::info('KaryawanController store called', $request->all());

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => [
                'required',
                'string',
                'regex:/^(08|\+62)[0-9]{8,17}$/'
            ],
            'jabatan' => 'required|string|in:admin,kasir,kurir',
            'password' => 'required|string|min:6',
            'status' => 'sometimes|in:aktif,nonaktif',
        ], [
            'telepon.regex' => 'Nomor telepon harus dimulai dengan 08 atau +62 dan hanya boleh berisi angka setelahnya.',
            'jabatan.in' => 'Jabatan harus salah satu dari: admin, kasir, kurir.',
        ]);

        try {
            // Set default status jika tidak disediakan
            $validated['status'] = $validated['status'] ?? 'aktif';

            Log::info('KaryawanController creating karyawan with data:', $validated);

            $karyawan = Karyawan::create($validated);

            Log::info('KaryawanController karyawan created successfully:', $karyawan->toArray());

            return response()->json([
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => $karyawan
            ], 201);
        } catch (\Exception $e) {
            Log::error('KaryawanController store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // SHOW - Tampilkan karyawan spesifik
    public function show($id)
    {
        try {
            $karyawan = Karyawan::findOrFail($id);
            return response()->json($karyawan);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Karyawan tidak ditemukan'], 404);
        }
    }

    // UPDATE - Edit data karyawan
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => ['required', 'string', 'regex:/^(08|\+62)[0-9]{8,17}$/'],
            'jabatan' => 'required|string|in:admin,kasir,kurir',
            'status' => 'required|string|in:aktif,nonaktif',
        ], [
            'telepon.regex' => 'Nomor telepon harus dimulai dengan 08 atau +62 dan hanya boleh berisi angka setelahnya.',
            'jabatan.in' => 'Jabatan harus salah satu dari: admin, kasir, kurir.',
        ]);

        try {
            $karyawan = Karyawan::findOrFail($id);
            $karyawan->update($validated);

            return response()->json([
                'message' => 'Data karyawan berhasil diperbarui',
                'data' => $karyawan
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui data'], 500);
        }
    }

    // DESTROY - Hapus karyawan
    public function destroy($id)
    {
        try {
            $karyawan = Karyawan::findOrFail($id);
            $karyawan->delete();
            
            return response()->json(['message' => 'Karyawan berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus karyawan'], 500);
        }
    }

    // UBAH PASSWORD - Simpan langsung tanpa hash
    public function ubahPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        try {
            $karyawan = Karyawan::findOrFail($id);
            $karyawan->update(['password' => $request->password]);

            return response()->json(['message' => 'Password berhasil diubah'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengubah password'], 500);
        }
    }

    // TOGGLE STATUS - Aktifkan/nonaktifkan karyawan
    public function toggleStatus($id)
    {
        try {
            $karyawan = Karyawan::findOrFail($id);
            $newStatus = $karyawan->status === 'aktif' ? 'nonaktif' : 'aktif';
            
            $karyawan->update(['status' => $newStatus]);

            return response()->json([
                'message' => "Status karyawan berhasil diubah menjadi $newStatus",
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengubah status'], 500);
        }
    }
}