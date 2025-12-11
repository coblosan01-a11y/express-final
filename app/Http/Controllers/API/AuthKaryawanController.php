<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Log;

class AuthKaryawanController extends Controller
{
    // Login karyawan (admin, kasir, kurir)
    public function login(Request $request)
    {
        $request->validate([
            'nama' => 'required|string',
            'password' => 'required|string',
        ]);

        Log::info('AuthKaryawan login attempt', [
            'nama' => $request->nama,
            'password_length' => strlen($request->password)
        ]);
    
        try {
            $karyawan = Karyawan::where('nama', $request->nama)->first();
    
            if (!$karyawan) {
                Log::warning('AuthKaryawan login: User not found', ['nama' => $request->nama]);
                return response()->json(['message' => 'Nama atau password salah'], 401);
            }

            Log::info('AuthKaryawan login: User found', [
                'id' => $karyawan->id,
                'nama' => $karyawan->nama,
                'jabatan' => $karyawan->jabatan,
                'status' => $karyawan->status
            ]);

            // Validasi password
            if ($karyawan->password !== $request->password) {
                Log::warning('AuthKaryawan login: Wrong password', ['nama' => $request->nama]);
                return response()->json(['message' => 'Nama atau password salah'], 401);
            }

            // Cek status karyawan
            if ($karyawan->status !== 'aktif') {
                Log::warning('AuthKaryawan login: Inactive account', ['nama' => $request->nama]);
                return response()->json(['message' => 'Akun karyawan tidak aktif'], 403);
            }

            // Validasi jabatan yang diizinkan
            $allowedJabatan = ['admin', 'kasir', 'kurir'];
            if (!in_array($karyawan->jabatan, $allowedJabatan)) {
                Log::warning('AuthKaryawan login: Invalid role', [
                    'nama' => $request->nama,
                    'jabatan' => $karyawan->jabatan
                ]);
                return response()->json(['message' => 'Jabatan tidak diizinkan untuk login'], 403);
            }

            Log::info('AuthKaryawan login success', [
                'user_id' => $karyawan->id,
                'jabatan' => $karyawan->jabatan
            ]);
    
            return response()->json([
                'message' => 'Login berhasil',
                'user' => $karyawan,
                'role' => 'karyawan',
                'access_level' => $karyawan->getAccessLevel(),
                'permissions' => $this->getPermissions($karyawan->jabatan)
            ]);
        } catch (\Exception $e) {
            Log::error('AuthKaryawan login error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get permissions berdasarkan jabatan karyawan
    private function getPermissions($jabatan)
    {
        switch ($jabatan) {
            case 'admin':
                return [
                    'manage_admins' => false, // Hanya pemilik
                    'manage_karyawan' => true,
                    'manage_layanan' => true,
                    'view_reports' => true,
                    'manage_transactions' => true,
                    'manage_orders' => true,
                    'system_settings' => false, // Hanya pemilik
                    'financial_reports' => false, // Hanya pemilik
                ];
            case 'kasir':
                return [
                    'manage_admins' => false,
                    'manage_karyawan' => false,
                    'manage_layanan' => false,
                    'view_reports' => true, // Hanya laporan harian sendiri
                    'manage_transactions' => true,
                    'manage_orders' => false,
                    'system_settings' => false,
                    'financial_reports' => false,
                ];
            case 'kurir':
                return [
                    'manage_admins' => false,
                    'manage_karyawan' => false,
                    'manage_layanan' => false,
                    'view_reports' => false,
                    'manage_transactions' => false,
                    'manage_orders' => true, // Hanya lihat dan update status orderan
                    'system_settings' => false,
                    'financial_reports' => false,
                ];
            default:
                return [];
        }
    }
}