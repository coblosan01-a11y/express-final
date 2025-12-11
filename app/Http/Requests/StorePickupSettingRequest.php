<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\PickupSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StorePickupSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'jarak_min' => [
                'required',
                'numeric',
                'min:0',
                'max:999.99'
            ],
            'jarak_max' => [
                'required',
                'numeric',
                'min:0',
                'max:999.99',
                'gt:jarak_min', // Must be greater than jarak_min
                function ($attribute, $value, $fail) {
                    $this->validateRangeOverlap($attribute, $value, $fail);
                }
            ],
            'biaya' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999' // 99 million max
            ],
            'service_type' => [
                'required', // ← CHANGED from nullable to required
                'in:pickup_only,pickup_delivery,delivery_only' // ← ADDED delivery_only
            ],
            'pickup_only' => [
                'nullable',
                'boolean'
            ],
            'pickup_delivery' => [
                'nullable',
                'boolean'
            ],
            'delivery_only' => [ // ← NEW
                'nullable',
                'boolean'
            ],
            'deskripsi' => [
                'nullable',
                'string',
                'max:255'
            ],
            'aktif' => [
                'nullable',
                'boolean'
            ]
        ];
    }

    /**
     * Validate range overlap for same service type
     */
    private function validateRangeOverlap($attribute, $value, $fail)
    {
        $jarakMin = (float) $this->input('jarak_min');
        $jarakMax = (float) $value;
        
        // Determine service type
        $serviceType = $this->getServiceType();
        
        if (!$serviceType) {
            return; // Let other validation handle this
        }

        // Check for overlap with same service type ONLY
        $query = DB::table('pickup_settings')
            ->where('aktif', true); // Only check active settings
        
        // Filter by service type
        if ($serviceType === 'pickup_only') {
            $query->where(function($q) {
                $q->where('service_type', 'pickup_only')
                  ->orWhere(function($subQ) {
                      $subQ->where('pickup_only', true)
                           ->where('pickup_delivery', false)
                           ->where('delivery_only', false);
                  });
            });
        } elseif ($serviceType === 'pickup_delivery') {
            $query->where(function($q) {
                $q->where('service_type', 'pickup_delivery')
                  ->orWhere(function($subQ) {
                      $subQ->where('pickup_delivery', true);
                  });
            });
        } elseif ($serviceType === 'delivery_only') { // ← NEW
            $query->where(function($q) {
                $q->where('service_type', 'delivery_only')
                  ->orWhere(function($subQ) {
                      $subQ->where('delivery_only', true)
                           ->where('pickup_only', false)
                           ->where('pickup_delivery', false);
                  });
            });
        }
        
        $hasOverlap = $query->where(function($q) use ($jarakMin, $jarakMax) {
                // Check range overlap: new range overlaps existing if
                // newMin < existingMax AND newMax > existingMin
                $q->where('jarak_min', '<', $jarakMax)
                  ->where('jarak_max', '>', $jarakMin);
            })
            ->exists();

        if ($hasOverlap) {
            $serviceLabel = $this->getServiceLabel($serviceType);
            $fail("Rentang jarak {$jarakMin}-{$jarakMax} km overlap dengan pengaturan yang sudah ada untuk layanan {$serviceLabel}");
        }
    }

    /**
     * Get service type from request (support both old and new format)
     */
    private function getServiceType(): ?string
    {
        // New format (prioritized)
        if ($this->has('service_type') && $this->input('service_type')) {
            return $this->input('service_type');
        }
        
        // Old format (backward compatibility)
        $pickupOnly = $this->input('pickup_only', false);
        $pickupDelivery = $this->input('pickup_delivery', false);
        $deliveryOnly = $this->input('delivery_only', false);
        
        if ($pickupOnly && !$pickupDelivery && !$deliveryOnly) {
            return 'pickup_only';
        } elseif ($pickupDelivery) {
            return 'pickup_delivery';
        } elseif ($deliveryOnly && !$pickupOnly && !$pickupDelivery) {
            return 'delivery_only';
        }
        
        return null;
    }

    /**
     * Get service label for error messages
     */
    private function getServiceLabel(string $serviceType): string
    {
        return match($serviceType) {
            'pickup_only' => 'Ambil Saja',
            'pickup_delivery' => 'Ambil + Antar',
            'delivery_only' => 'Antar Saja',
            default => 'Tidak Diketahui'
        };
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'jarak_min.required' => 'Jarak minimum wajib diisi',
            'jarak_min.numeric' => 'Jarak minimum harus berupa angka',
            'jarak_min.min' => 'Jarak minimum tidak boleh kurang dari 0',
            'jarak_min.max' => 'Jarak minimum tidak boleh lebih dari 999.99 km',
            
            'jarak_max.required' => 'Jarak maksimum wajib diisi',
            'jarak_max.numeric' => 'Jarak maksimum harus berupa angka',
            'jarak_max.min' => 'Jarak maksimum tidak boleh kurang dari 0',
            'jarak_max.max' => 'Jarak maksimum tidak boleh lebih dari 999.99 km',
            'jarak_max.gt' => 'Jarak maksimum harus lebih besar dari jarak minimum',
            
            'biaya.required' => 'Biaya pickup wajib diisi',
            'biaya.numeric' => 'Biaya pickup harus berupa angka',
            'biaya.min' => 'Biaya pickup tidak boleh kurang dari 0',
            'biaya.max' => 'Biaya pickup tidak boleh lebih dari 99,999,999',
            
            'service_type.required' => 'Jenis layanan wajib dipilih',
            'service_type.in' => 'Jenis layanan harus pickup_only, pickup_delivery, atau delivery_only',
            
            'pickup_only.boolean' => 'Pickup only harus berupa true/false',
            'pickup_delivery.boolean' => 'Pickup delivery harus berupa true/false',
            'delivery_only.boolean' => 'Delivery only harus berupa true/false',
            
            'deskripsi.string' => 'Deskripsi harus berupa teks',
            'deskripsi.max' => 'Deskripsi tidak boleh lebih dari 255 karakter',
            
            'aktif.boolean' => 'Status aktif harus berupa true/false'
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'jarak_min' => 'jarak minimum',
            'jarak_max' => 'jarak maksimum',
            'biaya' => 'biaya pickup',
            'service_type' => 'jenis layanan',
            'pickup_only' => 'pickup only',
            'pickup_delivery' => 'pickup delivery',
            'delivery_only' => 'delivery only',
            'deskripsi' => 'deskripsi',
            'aktif' => 'status aktif'
        ];
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        // Convert numeric strings to actual numbers
        if ($this->has('jarak_min')) {
            $this->merge([
                'jarak_min' => (float) $this->jarak_min
            ]);
        }

        if ($this->has('jarak_max')) {
            $this->merge([
                'jarak_max' => (float) $this->jarak_max
            ]);
        }

        if ($this->has('biaya')) {
            $this->merge([
                'biaya' => (float) $this->biaya
            ]);
        }

        // Set default aktif to true if not provided
        if (!$this->has('aktif')) {
            $this->merge([
                'aktif' => true
            ]);
        }
    }
}