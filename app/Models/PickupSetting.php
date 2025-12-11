<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickupSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'jarak_min',
        'jarak_max',
        'biaya',
        'service_type',
        'pickup_only',
        'pickup_delivery',
        'delivery_only',      // ← ADDED
        'deskripsi',
        'aktif'
    ];

    protected $casts = [
        'jarak_min' => 'decimal:2',
        'jarak_max' => 'decimal:2',
        'biaya' => 'decimal:2',
        'pickup_only' => 'boolean',
        'pickup_delivery' => 'boolean',
        'delivery_only' => 'boolean',  // ← ADDED
        'aktif' => 'boolean'
    ];

    // ===============================
    // SCOPES
    // ===============================

    /**
     * Scope untuk hanya setting yang aktif
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk filter berdasarkan jarak
     */
    public function scopeForJarak($query, $jarak)
    {
        return $query->where('jarak_min', '<=', $jarak)
                    ->where('jarak_max', '>=', $jarak);
    }

    /**
     * Scope untuk filter berdasarkan service type - UPDATED with delivery_only
     */
    public function scopeWithService($query, $serviceType)
    {
        if ($serviceType === 'pickup_only') {
            // Check both new format and old format
            return $query->where(function($q) {
                $q->where('service_type', 'pickup_only')
                  ->orWhere(function($subQ) {
                      $subQ->where('pickup_only', true)
                           ->where('pickup_delivery', false)
                           ->where('delivery_only', false);
                  });
            });
        } elseif ($serviceType === 'pickup_delivery') {
            // Check both new format and old format
            return $query->where(function($q) {
                $q->where('service_type', 'pickup_delivery')
                  ->orWhere(function($subQ) {
                      $subQ->where('pickup_delivery', true);
                  });
            });
        } elseif ($serviceType === 'delivery_only') {
            // NEW: Check delivery only service
            return $query->where(function($q) {
                $q->where('service_type', 'delivery_only')
                  ->orWhere(function($subQ) {
                      $subQ->where('delivery_only', true)
                           ->where('pickup_only', false)
                           ->where('pickup_delivery', false);
                  });
            });
        }
        
        return $query;
    }

    /**
     * New scope for service type filtering (using service_type column directly)
     */
    public function scopeByServiceType($query, $serviceType)
    {
        return $query->where('service_type', $serviceType);
    }

    // ===============================
    // STATIC METHODS
    // ===============================

    /**
     * Hitung biaya pickup berdasarkan jarak dan service type - UPDATED
     */
    public static function hitungBiayaPickup($jarak, $serviceType = 'pickup_only')
    {
        $setting = static::aktif()
            ->forJarak($jarak)
            ->withService($serviceType)
            ->first();

        if (!$setting) {
            return null;
        }

        return [
            'jarak' => $jarak,
            'biaya' => floatval($setting->biaya),
            'rentang' => "{$setting->jarak_min} - {$setting->jarak_max} km",
            'deskripsi' => $setting->deskripsi,
            'setting_id' => $setting->id,
            'service_type' => $serviceType,
            'service_label' => $setting->service_label,
            'service_icon' => $setting->service_icon,
            'service_available' => [
                'pickup_only' => $setting->pickup_only,
                'pickup_delivery' => $setting->pickup_delivery,
                'delivery_only' => $setting->delivery_only
            ]
        ];
    }

    /**
     * Get semua rentang jarak yang aktif - UPDATED
     */
    public static function getRentangJarak()
    {
        return static::aktif()
            ->orderBy('service_type')
            ->orderBy('jarak_min')
            ->get()
            ->map(function($setting) {
                return [
                    'id' => $setting->id,
                    'jarak_min' => $setting->jarak_min,
                    'jarak_max' => $setting->jarak_max,
                    'biaya' => floatval($setting->biaya),
                    'service_type' => $setting->service_type,
                    'pickup_only' => $setting->pickup_only,
                    'pickup_delivery' => $setting->pickup_delivery,
                    'delivery_only' => $setting->delivery_only,
                    'deskripsi' => $setting->deskripsi,
                    'aktif' => $setting->aktif,
                    'rentang' => "{$setting->jarak_min} - {$setting->jarak_max} km",
                    'biaya_formatted' => 'Rp ' . number_format($setting->biaya, 0, ',', '.'),
                    'service_label' => $setting->service_label,
                    'service_icon' => $setting->service_icon,
                    'services' => [
                        ...($setting->pickup_only ? ['pickup_only'] : []),
                        ...($setting->pickup_delivery ? ['pickup_delivery'] : []),
                        ...($setting->delivery_only ? ['delivery_only'] : [])
                    ],
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at
                ];
            });
    }

    /**
     * Cek apakah jarak dapat dilayani - UPDATED
     */
    public static function dapatDilayani($jarak, $serviceType = null)
    {
        $query = static::aktif()->forJarak($jarak);
        
        if ($serviceType) {
            $query->withService($serviceType);
        }
        
        return $query->exists();
    }

    /**
     * Get available services for specific distance
     */
    public static function getAvailableServicesForJarak($jarak)
    {
        $settings = static::aktif()
            ->forJarak($jarak)
            ->get();

        $availableServices = [];
        
        foreach ($settings as $setting) {
            $serviceType = $setting->effective_service_type;
            if ($serviceType && !isset($availableServices[$serviceType])) {
                $availableServices[$serviceType] = [
                    'id' => $setting->id,
                    'type' => $serviceType,
                    'label' => $setting->service_label,
                    'icon' => $setting->service_icon,
                    'biaya' => floatval($setting->biaya),
                    'rentang' => $setting->rentang
                ];
            }
        }

        return array_values($availableServices);
    }

    // ===============================
    // ACCESSORS
    // ===============================

    /**
     * Get rentang jarak dalam format string
     */
    public function getRentangAttribute()
    {
        return "{$this->jarak_min} - {$this->jarak_max} km";
    }

    /**
     * Get biaya dalam format currency
     */
    public function getBiayaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->biaya, 0, ',', '.');
    }

    /**
     * Get array of available services - UPDATED
     */
    public function getAvailableServicesAttribute()
    {
        $services = [];
        if ($this->pickup_only) $services[] = 'pickup_only';
        if ($this->pickup_delivery) $services[] = 'pickup_delivery';
        if ($this->delivery_only) $services[] = 'delivery_only';
        return $services;
    }

    /**
     * Get service names as comma-separated string - UPDATED
     */
    public function getServiceNamesAttribute()
    {
        $names = [];
        if ($this->pickup_only) $names[] = 'Ambil Saja';
        if ($this->pickup_delivery) $names[] = 'Ambil + Antar';
        if ($this->delivery_only) $names[] = 'Antar Saja';
        return implode(', ', $names);
    }

    /**
     * Get effective service type (prioritize service_type column) - UPDATED
     */
    public function getEffectiveServiceTypeAttribute()
    {
        // Return new format if available
        if ($this->service_type) {
            return $this->service_type;
        }
        
        // Fallback to old format
        if ($this->pickup_only && !$this->pickup_delivery && !$this->delivery_only) {
            return 'pickup_only';
        } elseif ($this->pickup_delivery) {
            return 'pickup_delivery';
        } elseif ($this->delivery_only && !$this->pickup_only && !$this->pickup_delivery) {
            return 'delivery_only';
        }
        
        return null;
    }

    /**
     * Get service icon - UPDATED
     */
    public function getServiceIconAttribute()
    {
        $serviceType = $this->effective_service_type;
        return match($serviceType) {
            'pickup_only' => '📦',
            'pickup_delivery' => '🚚',
            'delivery_only' => '🏠',
            default => '❓'
        };
    }

    /**
     * Get service label with icon - UPDATED
     */
    public function getServiceLabelAttribute()
    {
        $serviceType = $this->effective_service_type;
        return match($serviceType) {
            'pickup_only' => '📦 Ambil Saja',
            'pickup_delivery' => '🚚 Ambil + Antar',
            'delivery_only' => '🏠 Antar Saja',
            default => '❓ Tidak Diketahui'
        };
    }

    /**
     * Get service description - NEW
     */
    public function getServiceDescriptionAttribute()
    {
        $serviceType = $this->effective_service_type;
        return match($serviceType) {
            'pickup_only' => 'Hanya mengambil cucian di tempat pelanggan',
            'pickup_delivery' => 'Mengambil cucian dan mengantar kembali',
            'delivery_only' => 'Hanya mengantar cucian yang sudah selesai',
            default => ''
        };
    }

    /**
     * Check if this setting supports a specific service type - NEW
     */
    public function supportsService($serviceType)
    {
        return $this->effective_service_type === $serviceType;
    }

    /**
     * Check if this setting overlaps with another setting - NEW
     */
    public function overlapsWithRange($jarakMin, $jarakMax, $serviceType = null)
    {
        // Only check overlap if service type matches (if specified)
        if ($serviceType && $this->effective_service_type !== $serviceType) {
            return false;
        }

        // Check if ranges overlap
        return $this->jarak_min < $jarakMax && $this->jarak_max > $jarakMin;
    }

    // ===============================
    // VALIDATION HELPERS
    // ===============================

    /**
     * Check if there's any overlap with active settings - NEW
     */
    public function hasOverlapWithActive()
    {
        return static::where('aktif', true)
            ->where('id', '!=', $this->id)
            ->where('service_type', $this->service_type)
            ->where(function($query) {
                $query->where('jarak_min', '<', $this->jarak_max)
                      ->where('jarak_max', '>', $this->jarak_min);
            })
            ->exists();
    }

    /**
     * Validate service type - NEW
     */
    public static function isValidServiceType($serviceType)
    {
        return in_array($serviceType, ['pickup_only', 'pickup_delivery', 'delivery_only']);
    }

    /**
     * Get all valid service types - NEW
     */
    public static function getValidServiceTypes()
    {
        return [
            'pickup_only' => [
                'label' => 'Ambil Saja',
                'icon' => '📦',
                'description' => 'Hanya mengambil cucian di tempat pelanggan'
            ],
            'pickup_delivery' => [
                'label' => 'Ambil + Antar',
                'icon' => '🚚',
                'description' => 'Mengambil cucian dan mengantar kembali'
            ],
            'delivery_only' => [
                'label' => 'Antar Saja',
                'icon' => '🏠',
                'description' => 'Hanya mengantar cucian yang sudah selesai'
            ]
        ];
    }
}