<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PickupSetting;
use App\Http\Requests\StorePickupSettingRequest;
use App\Http\Requests\UpdatePickupSettingRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PickupSettingController extends Controller
{
    /**
     * Get all pickup settings
     * GET /api/pickup-settings
     */
    public function index()
    {
        try {
            $settings = PickupSetting::orderBy('service_type')
                ->orderBy('jarak_min')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Data pengaturan pickup berhasil dimuat'
            ]);

        } catch (\Exception $e) {
            Log::error('Get pickup settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat pengaturan pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active pickup settings only (for customer) - UPDATED with delivery_only
     * GET /api/pickup-settings/active
     */
    public function getActiveSettings()
    {
        try {
            $settings = PickupSetting::where('aktif', true)
                ->orderBy('service_type')
                ->orderBy('jarak_min')
                ->get()
                ->map(function($setting) {
                    return [
                        'id' => $setting->id,
                        'jarak_min' => $setting->jarak_min,
                        'jarak_max' => $setting->jarak_max,
                        'biaya' => $setting->biaya,
                        'service_type' => $setting->service_type,
                        'pickup_only' => $setting->pickup_only,
                        'pickup_delivery' => $setting->pickup_delivery,
                        'delivery_only' => $setting->delivery_only,
                        'deskripsi' => $setting->deskripsi,
                        'aktif' => $setting->aktif,
                        'rentang' => $setting->jarak_min . '-' . $setting->jarak_max . ' km',
                        'created_at' => $setting->created_at,
                        'updated_at' => $setting->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $settings,
                'message' => 'Data rentang pickup berhasil dimuat'
            ]);

        } catch (\Exception $e) {
            Log::error('Get active settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat rentang pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get specific pickup setting
     * GET /api/pickup-settings/{id}
     */
    public function show($id)
    {
        try {
            $setting = PickupSetting::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $setting,
                'message' => 'Detail pengaturan pickup berhasil dimuat'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaturan pickup tidak ditemukan'
            ], 404);
        }
    }

    /**
     * Create new pickup setting - UPDATED with delivery_only
     * POST /api/pickup-settings
     */
    public function store(StorePickupSettingRequest $request)
    {
        try {
            // Validate service_type
            if (!in_array($request->service_type, ['pickup_only', 'pickup_delivery', 'delivery_only'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis layanan tidak valid'
                ], 422);
            }

            // Set boolean flags based on service_type
            $pickupOnly = $request->service_type === 'pickup_only';
            $pickupDelivery = $request->service_type === 'pickup_delivery';
            $deliveryOnly = $request->service_type === 'delivery_only';

            // Check for overlapping ranges with same service type
            $hasOverlap = PickupSetting::where('aktif', true)
                ->where('service_type', $request->service_type)
                ->where(function($query) use ($request) {
                    $query->where('jarak_min', '<', $request->jarak_max)
                          ->where('jarak_max', '>', $request->jarak_min);
                })
                ->exists();

            if ($hasOverlap) {
                $serviceLabel = $this->getServiceLabel($request->service_type);
                return response()->json([
                    'success' => false,
                    'message' => "Rentang jarak overlap dengan pengaturan {$serviceLabel} yang sudah ada"
                ], 422);
            }

            $setting = PickupSetting::create([
                'jarak_min' => $request->jarak_min,
                'jarak_max' => $request->jarak_max,
                'biaya' => $request->biaya,
                'service_type' => $request->service_type,
                'pickup_only' => $pickupOnly,
                'pickup_delivery' => $pickupDelivery,
                'delivery_only' => $deliveryOnly,
                'deskripsi' => $request->deskripsi,
                'aktif' => $request->aktif ?? true
            ]);

            // Clear cache
            Cache::forget('active_pickup_settings');

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan pickup berhasil dibuat',
                'data' => $setting
            ], 201);

        } catch (\Exception $e) {
            Log::error('Create pickup setting error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pengaturan pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update pickup setting - UPDATED with delivery_only
     * PUT /api/pickup-settings/{id}
     */
    public function update(UpdatePickupSettingRequest $request, $id)
    {
        try {
            $setting = PickupSetting::findOrFail($id);

            // Validate service_type
            if (!in_array($request->service_type, ['pickup_only', 'pickup_delivery', 'delivery_only'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jenis layanan tidak valid'
                ], 422);
            }

            // Set boolean flags based on service_type
            $pickupOnly = $request->service_type === 'pickup_only';
            $pickupDelivery = $request->service_type === 'pickup_delivery';
            $deliveryOnly = $request->service_type === 'delivery_only';

            // Check for overlapping ranges with same service type (excluding current setting)
            $hasOverlap = PickupSetting::where('aktif', true)
                ->where('id', '!=', $id)
                ->where('service_type', $request->service_type)
                ->where(function($query) use ($request) {
                    $query->where('jarak_min', '<', $request->jarak_max)
                          ->where('jarak_max', '>', $request->jarak_min);
                })
                ->exists();

            if ($hasOverlap) {
                $serviceLabel = $this->getServiceLabel($request->service_type);
                return response()->json([
                    'success' => false,
                    'message' => "Rentang jarak overlap dengan pengaturan {$serviceLabel} yang sudah ada"
                ], 422);
            }

            $setting->update([
                'jarak_min' => $request->jarak_min,
                'jarak_max' => $request->jarak_max,
                'biaya' => $request->biaya,
                'service_type' => $request->service_type,
                'pickup_only' => $pickupOnly,
                'pickup_delivery' => $pickupDelivery,
                'delivery_only' => $deliveryOnly,
                'deskripsi' => $request->deskripsi,
                'aktif' => $request->aktif ?? $setting->aktif
            ]);

            // Clear cache
            Cache::forget('active_pickup_settings');

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan pickup berhasil diupdate',
                'data' => $setting->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Update pickup setting error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengaturan pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete pickup setting
     * DELETE /api/pickup-settings/{id}
     */
    public function destroy($id)
    {
        try {
            $setting = PickupSetting::findOrFail($id);
            
            // Optional: Check if setting is being used in orders
            // Uncomment when pickup orders model is ready
            /*
            $isUsed = PickupOrder::where('setting_id', $id)->exists();
            if ($isUsed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaturan tidak dapat dihapus karena masih digunakan dalam order'
                ], 422);
            }
            */

            $setting->delete();

            // Clear cache
            Cache::forget('active_pickup_settings');

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan pickup berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete pickup setting error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengaturan pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Toggle status aktif/nonaktif - UPDATED with delivery_only
     * PATCH /api/pickup-settings/{id}/toggle
     */
    public function toggleStatus($id)
    {
        try {
            $setting = PickupSetting::findOrFail($id);
            $newStatus = !$setting->aktif;

            // Check overlap jika akan diaktifkan
            if ($newStatus) {
                $hasOverlap = PickupSetting::where('aktif', true)
                    ->where('id', '!=', $id)
                    ->where('service_type', $setting->service_type)
                    ->where(function($query) use ($setting) {
                        $query->where('jarak_min', '<', $setting->jarak_max)
                              ->where('jarak_max', '>', $setting->jarak_min);
                    })
                    ->exists();

                if ($hasOverlap) {
                    $serviceLabel = $this->getServiceLabel($setting->service_type);
                    return response()->json([
                        'success' => false,
                        'message' => "Tidak dapat mengaktifkan karena rentang jarak overlap dengan setting aktif lain untuk layanan {$serviceLabel}"
                    ], 422);
                }
            }

            $setting->update(['aktif' => $newStatus]);

            // Clear cache
            Cache::forget('active_pickup_settings');

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil ' . ($newStatus ? 'diaktifkan' : 'dinonaktifkan'),
                'data' => $setting->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Toggle status error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Calculate pickup cost based on distance - UPDATED with delivery_only
     * GET /api/pickup-settings/calculate?jarak=7.5&service_type=pickup_only
     */
    public function calculateCost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jarak' => 'required|numeric|min:0|max:999.99',
            'service_type' => 'required|in:pickup_only,pickup_delivery,delivery_only'
        ], [
            'jarak.required' => 'Parameter jarak wajib diisi',
            'jarak.numeric' => 'Jarak harus berupa angka',
            'jarak.min' => 'Jarak tidak boleh kurang dari 0',
            'jarak.max' => 'Jarak tidak boleh lebih dari 999.99 km',
            'service_type.required' => 'Jenis layanan wajib diisi',
            'service_type.in' => 'Jenis layanan harus pickup_only, pickup_delivery, atau delivery_only'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jarak = $request->jarak;
            $serviceType = $request->service_type;

            $biayaInfo = PickupSetting::hitungBiayaPickup($jarak, $serviceType);

            if (!$biayaInfo) {
                // Get available ranges for suggestion
                $availableRanges = PickupSetting::where('aktif', true)
                    ->where('service_type', $serviceType)
                    ->orderBy('jarak_min')
                    ->get(['jarak_min', 'jarak_max', 'service_type'])
                    ->map(function($range) {
                        return [
                            'range' => "{$range->jarak_min}-{$range->jarak_max} km",
                            'service' => $this->getServiceLabel($range->service_type)
                        ];
                    })
                    ->toArray();

                $serviceLabel = $this->getServiceLabel($serviceType);

                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada tarif untuk jarak {$jarak} km dengan layanan {$serviceLabel}",
                    'available_ranges' => $availableRanges
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $biayaInfo,
                'message' => 'Biaya pickup berhasil dihitung'
            ]);

        } catch (\Exception $e) {
            Log::error('Calculate cost error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghitung biaya pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if distance can be served - UPDATED with delivery_only
     * GET /api/pickup-settings/check?jarak=7.5&service_type=pickup_only
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jarak' => 'required|numeric|min:0|max:999.99',
            'service_type' => 'required|in:pickup_only,pickup_delivery,delivery_only'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jarak = $request->jarak;
            $serviceType = $request->service_type;
            
            $dapatDilayani = PickupSetting::dapatDilayani($jarak, $serviceType);
            $serviceLabel = $this->getServiceLabel($serviceType);

            return response()->json([
                'success' => true,
                'data' => [
                    'jarak' => $jarak,
                    'service_type' => $serviceType,
                    'service_label' => $serviceLabel,
                    'dapat_dilayani' => $dapatDilayani,
                    'message' => $dapatDilayani 
                        ? "Jarak {$jarak} km dengan layanan {$serviceLabel} dapat dilayani" 
                        : "Jarak {$jarak} km dengan layanan {$serviceLabel} tidak dapat dilayani"
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Check availability error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek ketersediaan layanan',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get pickup statistics (for admin dashboard) - UPDATED with delivery_only
     * GET /api/pickup-settings/stats
     */
    public function getStats()
    {
        try {
            $totalSettings = PickupSetting::count();
            $activeSettings = PickupSetting::where('aktif', true);
            
            $stats = [
                'total_settings' => $totalSettings,
                'active_settings' => $activeSettings->count(),
                'inactive_settings' => PickupSetting::where('aktif', false)->count(),
                'coverage_range' => [
                    'min_distance' => $activeSettings->min('jarak_min'),
                    'max_distance' => $activeSettings->max('jarak_max')
                ],
                'price_range' => [
                    'min_price' => $activeSettings->min('biaya'),
                    'max_price' => $activeSettings->max('biaya')
                ],
                'service_types' => [
                    'pickup_only_count' => PickupSetting::where('aktif', true)
                        ->where('service_type', 'pickup_only')->count(),
                    'pickup_delivery_count' => PickupSetting::where('aktif', true)
                        ->where('service_type', 'pickup_delivery')->count(),
                    'delivery_only_count' => PickupSetting::where('aktif', true)
                        ->where('service_type', 'delivery_only')->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Statistik pickup berhasil dimuat'
            ]);

        } catch (\Exception $e) {
            Log::error('Get pickup stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat statistik pickup',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get available service types for specific distance - UPDATED with delivery_only
     * GET /api/pickup-settings/services?jarak=7.5
     */
    public function getAvailableServices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jarak' => 'required|numeric|min:0|max:999.99'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak valid',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $jarak = $request->jarak;
            
            // Find all settings that can serve this distance
            $matchingSettings = PickupSetting::where('aktif', true)
                ->where('jarak_min', '<=', $jarak)
                ->where('jarak_max', '>=', $jarak)
                ->get();

            if ($matchingSettings->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada layanan untuk jarak {$jarak} km",
                    'data' => [
                        'jarak' => $jarak,
                        'available_services' => []
                    ]
                ], 404);
            }

            // Group by service type
            $availableServices = [];
            
            foreach (['pickup_only', 'pickup_delivery', 'delivery_only'] as $serviceType) {
                $setting = $matchingSettings->where('service_type', $serviceType)->first();
                
                if ($setting) {
                    $availableServices[] = [
                        'id' => $setting->id,
                        'type' => $serviceType,
                        'name' => $this->getServiceLabel($serviceType),
                        'description' => $this->getServiceDescription($serviceType),
                        'icon' => $this->getServiceIcon($serviceType),
                        'biaya' => $setting->biaya,
                        'rentang' => $setting->jarak_min . '-' . $setting->jarak_max . ' km'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'jarak' => $jarak,
                    'available_services' => $availableServices,
                    'total_services' => count($availableServices)
                ],
                'message' => 'Layanan tersedia berhasil dimuat'
            ]);

        } catch (\Exception $e) {
            Log::error('Get available services error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat layanan yang tersedia',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Helper: Get service label
     */
    private function getServiceLabel($serviceType)
    {
        return match($serviceType) {
            'pickup_only' => 'Ambil Saja',
            'pickup_delivery' => 'Ambil + Antar',
            'delivery_only' => 'Antar Saja',
            default => 'Tidak Diketahui'
        };
    }

    /**
     * Helper: Get service description
     */
    private function getServiceDescription($serviceType)
    {
        return match($serviceType) {
            'pickup_only' => 'Hanya mengambil cucian di tempat pelanggan',
            'pickup_delivery' => 'Mengambil cucian dan mengantar kembali',
            'delivery_only' => 'Hanya mengantar cucian yang sudah selesai',
            default => ''
        };
    }

    /**
     * Helper: Get service icon
     */
    private function getServiceIcon($serviceType)
    {
        return match($serviceType) {
            'pickup_only' => '📦',
            'pickup_delivery' => '🚚',
            'delivery_only' => '🏠',
            default => '❓'
        };
    }
}