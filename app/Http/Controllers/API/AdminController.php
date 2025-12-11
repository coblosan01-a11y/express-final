<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // Get all admins (hanya admin, tanpa pemilik)
    public function index()
    {
        try {
            // Hanya tampilkan admin, bukan pemilik
            $admins = Admin::where('jabatan', 'admin')->get();
            Log::info('AdminController index: Found ' . $admins->count() . ' admins');
            return response()->json($admins);
        } catch (\Exception $e) {
            Log::error('AdminController index error: ' . $e->getMessage());
            return response()->json(['message' => 'Gagal mengambil data admin'], 500);
        }
    }

    // Store new admin (hanya admin biasa, bukan pemilik)
    public function store(Request $request)
    {
        Log::info('AdminController store called with data: ', $request->all());

        $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        try {
            $data = [
                'nama' => $request->nama,
                'telepon' => $request->telepon,
                'jabatan' => 'admin', // Selalu admin, bukan pemilik
                'password' => $request->password,
                'status' => 'aktif',
            ];

            Log::info('AdminController attempting to create admin with data: ', $data);

            $admin = Admin::create($data);

            Log::info('AdminController admin created successfully: ', $admin->toArray());

            return response()->json([
                'message' => 'Admin berhasil ditambahkan',
                'admin' => $admin
            ], 201);
        } catch (\Exception $e) {
            Log::error('AdminController store error: ' . $e->getMessage());
            Log::error('AdminController store trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'message' => 'Gagal menambah admin',
                'error' => $e->getMessage() // Tambah detail error untuk debug
            ], 500);
        }
    }

    // Show specific admin
    public function show($id)
    {
        try {
            $admin = Admin::findOrFail($id);
            return response()->json($admin);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Admin tidak ditemukan'], 404);
        }
    }

    // Update admin
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:255',
            'status' => 'sometimes|in:aktif,nonaktif',
        ]);

        try {
            $admin = Admin::findOrFail($id);
            
            // Tidak bisa mengubah jabatan pemilik
            if ($admin->isPemilik()) {
                return response()->json(['message' => 'Tidak bisa mengubah data pemilik'], 403);
            }

            $admin->update([
                'nama' => $request->nama,
                'telepon' => $request->telepon,
                'status' => $request->status ?? $admin->status,
            ]);

            return response()->json([
                'message' => 'Admin berhasil diupdate',
                'admin' => $admin
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengupdate admin'], 500);
        }
    }

    // Delete admin (tidak bisa hapus pemilik)
    public function destroy($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            // Tidak bisa menghapus pemilik
            if ($admin->isPemilik()) {
                return response()->json(['message' => 'Tidak bisa menghapus pemilik'], 403);
            }

            $admin->delete();
            return response()->json(['message' => 'Admin berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus admin'], 500);
        }
    }

    // Change admin password
    public function ubahPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        try {
            $admin = Admin::findOrFail($id);
            $admin->update([
                'password' => $request->password,
            ]);

            return response()->json(['message' => 'Password berhasil diubah']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengubah password'], 500);
        }
    }

    // Toggle status admin
    public function toggleStatus($id)
    {
        try {
            $admin = Admin::findOrFail($id);

            // Tidak bisa nonaktifkan pemilik
            if ($admin->isPemilik()) {
                return response()->json(['message' => 'Tidak bisa mengubah status pemilik'], 403);
            }

            $newStatus = $admin->status === 'aktif' ? 'nonaktif' : 'aktif';
            $admin->update(['status' => $newStatus]);

            return response()->json([
                'message' => "Status admin berhasil diubah menjadi $newStatus",
                'status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengubah status'], 500);
        }
    }
}