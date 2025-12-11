<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Log;

class AuthAdminController extends Controller
{
    // Cek apakah sudah ada pemilik di database
    public function checkAdminExists()
    {
        try {
            $hasPemilik = Admin::hasPemilik();
            Log::info('checkAdminExists called, result: ' . ($hasPemilik ? 'true' : 'false'));
            
            return response()->json([
                'has_admin' => $hasPemilik
            ]);
        } catch (\Exception $e) {
            Log::error('checkAdminExists error: ' . $e->getMessage());
            return response()->json(['has_admin' => false]);
        }
    }

    // Registrasi pemilik pertama (setup awal sistem)
    public function registerFirstAdmin(Request $request)
    {
        Log::info('registerFirstAdmin called', $request->all());

        // Cek apakah sudah ada pemilik
        if (Admin::hasPemilik()) {
            Log::warning('registerFirstAdmin: Pemilik sudah ada');
            return response()->json(['message' => 'Pemilik sudah ada, setup tidak diizinkan'], 403);
        }

        $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        try {
            $pemilik = Admin::create([
                'nama' => $request->nama,
                'telepon' => $request->telepon,
                'jabatan' => 'pemilik',
                'password' => $request->password,
                'status' => 'aktif',
            ]);

            Log::info('registerFirstAdmin: Pemilik berhasil dibuat', ['id' => $pemilik->id]);

            return response()->json([
                'message' => 'Pemilik berhasil didaftarkan, sistem siap digunakan',
                'user' => $pemilik
            ]);
        } catch (\Exception $e) {
            Log::error('registerFirstAdmin error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Gagal melakukan setup', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Login admin panel (pemilik + karyawan admin)
    public function login(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'password' => 'required|string',
        ]);

        Log::info('AuthAdmin login attempt', [
            'nama' => $request->nama,
            'password_length' => strlen($request->password)
        ]);
    
        try {
            $user = null;
            $userType = null;

            // 1. Cek di tabel admins (pemilik)
            $admin = Admin::where('nama', $request->nama)->first();
            if ($admin) {
                $user = $admin;
                $userType = 'pemilik';
                Log::info('AuthAdmin: Found in admins table', [
                    'id' => $admin->id,
                    'jabatan' => $admin->jabatan
                ]);
            }

            // 2. Kalau tidak ada, cek di tabel karyawans dengan jabatan admin
            if (!$user) {
                $karyawanAdmin = Karyawan::where('nama', $request->nama)
                                         ->where('jabatan', 'admin')
                                         ->first();
                if ($karyawanAdmin) {
                    $user = $karyawanAdmin;
                    $userType = 'karyawan_admin';
                    Log::info('AuthAdmin: Found in karyawans table', [
                        'id' => $karyawanAdmin->id,
                        'jabatan' => $karyawanAdmin->jabatan
                    ]);
                }
            }

            // 3. User tidak ditemukan
            if (!$user) {
                Log::warning('AuthAdmin login: User not found', ['nama' => $request->nama]);
                return response()->json(['message' => 'Nama atau password salah'], 401);
            }

            // 4. Validasi password
            if ($user->password !== $request->password) {
                Log::warning('AuthAdmin login: Wrong password', [
                    'nama' => $request->nama,
                    'user_type' => $userType
                ]);
                return response()->json(['message' => 'Nama atau password salah'], 401);
            }

            // 5. Cek status user
            if ($user->status !== 'aktif') {
                Log::warning('AuthAdmin login: Inactive account', [
                    'nama' => $request->nama,
                    'user_type' => $userType
                ]);
                return response()->json(['message' => 'Akun tidak aktif'], 403);
            }

            Log::info('AuthAdmin login success', [
                'user_id' => $user->id,
                'user_type' => $userType,
                'jabatan' => $user->jabatan
            ]);

            // 6. Return response berdasarkan tipe user
            if ($userType === 'pemilik') {
                return response()->json([
                    'message' => 'Login berhasil',
                    'user' => $user,
                    'role' => 'pemilik',
                    'access_level' => 'full',
                    'permissions' => $this->getPermissions('pemilik')
                ]);
            } else {
                return response()->json([
                    'message' => 'Login berhasil',
                    'user' => $user,
                    'role' => 'karyawan_admin',
                    'access_level' => 'high',
                    'permissions' => $this->getPermissions('karyawan_admin')
                ]);
            }

        } catch (\Exception $e) {
            Log::error('AuthAdmin login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat login', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get permissions berdasarkan tipe user
    private function getPermissions($type)
    {
        switch ($type) {
            case 'pemilik':
                return [
                    'manage_admins' => true,
                    'manage_karyawan' => true,
                    'manage_layanan' => true,
                    'view_all_reports' => true,
                    'manage_transactions' => true,
                    'manage_orders' => true,
                    'system_settings' => true,
                    'financial_reports' => true,
                ];
            case 'karyawan_admin':
                return [
                    'manage_admins' => false, // Hanya pemilik yang bisa manage admin lain
                    'manage_karyawan' => true,
                    'manage_layanan' => true,
                    'view_all_reports' => true,
                    'manage_transactions' => true,
                    'manage_orders' => true,
                    'system_settings' => false, // Hanya pemilik
                    'financial_reports' => false, // Hanya pemilik
                ];
            default:
                return [];
        }
    }
}