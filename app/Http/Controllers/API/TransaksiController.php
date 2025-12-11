<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaksi;
use App\Models\TransaksiDetail;
use App\Models\CashFlow;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TransaksiController extends Controller
{
    /**
     * ✅ NEW METHOD: Get orders for dashboard kasir with enhanced filtering
     * Khusus untuk DashboardKasir.vue
     */
    public function orders(Request $request)
    {
        try {
            $query = Transaksi::with(['customer', 'details'])
                ->orderBy('tanggal_transaksi', 'desc');

            // Enhanced filtering untuk dashboard kasir
            if ($request->date_from && $request->date_to) {
                $query->whereBetween('tanggal_transaksi', [
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay()
                ]);
            }

            // Filter by payment method
            if ($request->payment_method) {
                $query->where('metode_pembayaran', $request->payment_method);
            }

            // Filter by transaction status
            if ($request->status_transaksi) {
                $query->where('status_transaksi', $request->status_transaksi);
            }

            // Filter by laundry status (status_cucian)
            if ($request->status_cucian) {
                $query->where('status_cucian', $request->status_cucian);
            }

            // Filter berdasarkan jenis transaksi
            if ($request->transaction_type) {
                switch ($request->transaction_type) {
                    case 'laundry_only':
                        $query->where('has_pickup_service', false)
                              ->whereHas('details');
                        break;
                    case 'pickup_only':
                        $query->where('has_pickup_service', true)
                              ->whereDoesntHave('details');
                        break;
                    case 'combined':
                        $query->where('has_pickup_service', true)
                              ->whereHas('details');
                        break;
                }
            }

            // Filter pickup service
            if ($request->has('has_pickup')) {
                $query->where('has_pickup_service', (bool) $request->has_pickup);
            }

            // Search functionality
            if ($request->search) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('customer_name', 'like', $searchTerm)
                      ->orWhere('customer_phone', 'like', $searchTerm)
                      ->orWhere('kode_transaksi', 'like', $searchTerm)
                      ->orWhere('pickup_service_name', 'like', $searchTerm);
                });
            }

            // Get data (without pagination for dashboard)
            $orders = $query->get();

            // Enhanced response dengan data yang dibutuhkan dashboard
            $ordersData = $orders->map(function ($transaksi) {
                return [
                    'id' => $transaksi->id,
                    'kode_transaksi' => $transaksi->kode_transaksi,
                    'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                    'customer_id' => $transaksi->customer_id,
                    'customer_name' => $transaksi->customer_name,
                    'customer_phone' => $transaksi->customer_phone,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'total_amount' => (float) $transaksi->total_amount,
                    'subtotal_layanan' => (float) $transaksi->subtotal_layanan,
                    'biaya_pickup' => (float) $transaksi->biaya_pickup,
                    'jumlah_bayar' => (float) $transaksi->jumlah_bayar,
                    'kembalian' => (float) $transaksi->kembalian,
                    'status_transaksi' => $transaksi->status_transaksi,
                    'status_cucian' => $transaksi->status_cucian,
                    'has_pickup_service' => (bool) $transaksi->has_pickup_service,
                    'service_type' => $transaksi->pickup_service_type, // ✅ TAMBAH INI - Service type di top level
                    'pickup_status' => $transaksi->pickup_status ?? 'menunggu',
                    'catatan' => $transaksi->catatan,
                    'created_by' => $transaksi->created_by,
                    'kasir_id' => $transaksi->kasir_id ?? $transaksi->created_by,
                    'kasir_name' => $transaksi->kasir_name ?? 'System',
                    
                    // Items/Details
                    'items' => $transaksi->details->map(function ($detail) {
                        return [
                            'id' => $detail->id,
                            'layanan_id' => $detail->layanan_id,
                            'layanan_nama' => $detail->layanan_nama,
                            'kuantitas' => json_decode($detail->kuantitas, true),
                            'harga_satuan' => json_decode($detail->harga_satuan, true),
                            'subtotal' => (float) $detail->subtotal,
                            'catatan' => $detail->catatan,
                        ];
                    }),
                    
                    // Pickup service data (jika ada)
                    'pickup_service' => $transaksi->has_pickup_service ? [
                        'setting_id' => $transaksi->pickup_setting_id,
                        'service_name' => $transaksi->pickup_service_name,
                        'service_type' => $transaksi->pickup_service_type,
                        'jarak' => (float) $transaksi->pickup_jarak,
                        'rentang' => $transaksi->pickup_rentang,
                        'pickup_date' => $transaksi->pickup_date,
                        'pickup_time' => $transaksi->pickup_time,
                        'special_instructions' => $transaksi->pickup_special_instructions,
                        'base_cost' => (float) $transaksi->pickup_base_cost,
                        'total_cost' => (float) $transaksi->pickup_total_cost,
                    ] : null,
                    
                    // Transaction type
                    'transaction_type' => $this->determineTransactionType(
                        $transaksi->details()->exists(), 
                        $transaksi->has_pickup_service
                    ),
                    
                    // Timestamps
                    'created_at' => $transaksi->created_at,
                    'updated_at' => $transaksi->updated_at,
                ];
            });

            // Statistics untuk dashboard
            $stats = [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->where('status_transaksi', 'sukses')->sum('total_amount'),
                'pending_orders' => $orders->where('status_cucian', 'pending')->count(),
                'processing_orders' => $orders->where('status_cucian', 'processing')->count(),
                'completed_orders' => $orders->where('status_cucian', 'completed')->count(),
                'delivered_orders' => $orders->where('status_cucian', 'delivered')->count(),
                'pickup_orders' => $orders->where('has_pickup_service', true)->count(),
                'laundry_revenue' => $orders->where('status_transaksi', 'sukses')->sum('subtotal_layanan'),
                'pickup_revenue' => $orders->where('status_transaksi', 'sukses')->sum('biaya_pickup'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Orders loaded successfully',
                'data' => $ordersData,
                'statistics' => $stats,
                'filters_applied' => $request->only([
                    'date_from', 'date_to', 'payment_method', 'status_transaksi', 
                    'status_cucian', 'transaction_type', 'has_pickup', 'search'
                ]),
                'total_count' => $orders->count(),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('ORDERS API ERROR: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data orders',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * ✅ NEW METHOD: Update payment for completed orders
     */
    public function updatePayment(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'metode_pembayaran' => 'required|in:tunai,qris',
            'status_transaksi' => 'sometimes|in:sukses',
            'jumlah_bayar' => 'sometimes|numeric|min:0',
        ], [
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih',
            'metode_pembayaran.in' => 'Metode pembayaran tidak valid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaksi = Transaksi::findOrFail($id);
            
            // Validasi: hanya bisa update payment jika status_cucian = completed
            if ($transaksi->status_cucian !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembayaran hanya bisa diproses untuk order yang sudah selesai dicuci'
                ], 400);
            }

            // Validasi: hanya untuk order "bayar nanti" yang belum lunas
            if ($transaksi->metode_pembayaran !== 'bayar-nanti' || $transaksi->status_transaksi === 'sukses') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ini sudah lunas atau bukan "bayar nanti"'
                ], 400);
            }

            DB::beginTransaction();

            $oldMethod = $transaksi->metode_pembayaran;
            $oldStatus = $transaksi->status_transaksi;

            // Update payment data
            $updateData = [
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_transaksi' => 'sukses',
                'updated_at' => now()
            ];

            // Set jumlah_bayar jika tunai
            if ($request->metode_pembayaran === 'tunai') {
                $jumlahBayar = $request->jumlah_bayar ?? $transaksi->total_amount;
                $updateData['jumlah_bayar'] = $jumlahBayar;
                $updateData['kembalian'] = max(0, $jumlahBayar - $transaksi->total_amount);
            } else {
                // QRIS
                $updateData['jumlah_bayar'] = $transaksi->total_amount;
                $updateData['kembalian'] = 0;
            }

            $transaksi->update($updateData);

            // Create cash flow
            $this->createCashFlow($transaksi->fresh());

            DB::commit();

            Log::info('PAYMENT UPDATED', [
                'transaksi_id' => $id,
                'old_method' => $oldMethod,
                'new_method' => $request->metode_pembayaran,
                'old_status' => $oldStatus,
                'new_status' => 'sukses',
                'total_amount' => $transaksi->total_amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran berhasil diproses',
                'data' => [
                    'id' => $transaksi->id,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'status_transaksi' => $transaksi->status_transaksi,
                    'jumlah_bayar' => (float) $transaksi->jumlah_bayar,
                    'kembalian' => (float) $transaksi->kembalian,
                    'updated_at' => $transaksi->updated_at
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('UPDATE PAYMENT ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW METHOD: Update pickup status for pickup service orders
     */
    public function updatePickupStatus(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        // ✅ FIXED: Update validation rule sesuai status yang disederhanakan
        'pickup_status' => 'required|in:dioutlet,diantar,selesai'
    ], [
        'pickup_status.required' => 'Status pickup wajib diisi',
        'pickup_status.in' => 'Status pickup tidak valid (harus: dioutlet, diantar, atau selesai)'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $transaksi = Transaksi::findOrFail($id);
        
        // Validasi: hanya untuk order yang ada pickup service
        if (!$transaksi->has_pickup_service) {
            return response()->json([
                'success' => false,
                'message' => 'Order ini tidak memiliki layanan pickup'
            ], 400);
        }

        $oldPickupStatus = $transaksi->pickup_status;

        // Update pickup status
        $transaksi->update([
            'pickup_status' => $request->pickup_status,
            'updated_at' => now()
        ]);

        Log::info('PICKUP STATUS UPDATED', [
            'transaksi_id' => $id,
            'old_pickup_status' => $oldPickupStatus,
            'new_pickup_status' => $request->pickup_status,
            'kode_transaksi' => $transaksi->kode_transaksi
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pickup berhasil diupdate',
            'data' => [
                'id' => $transaksi->id,
                'pickup_status' => $transaksi->pickup_status,
                'updated_at' => $transaksi->updated_at
            ]
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Transaksi tidak ditemukan'
        ], 404);
    } catch (\Exception $e) {
        Log::error('UPDATE PICKUP STATUS ERROR: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Gagal mengupdate status pickup: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Store a new transaction with optional pickup service
     * Supports: Laundry only, Pickup only, or Combined transactions
     */
    public function store(Request $request)
    {
        Log::info('🚨 STORE TRANSAKSI DEBUG', [
            'full_request' => $request->all(),
            'has_pickup_service' => $request->has('pickup_service'),
            'pickup_service_value' => $request->input('pickup_service'),
            'pickup_service_type' => gettype($request->input('pickup_service')),
            'request_keys' => array_keys($request->all()),
            'auth_user' => Auth::user() // Log user yang sedang login
        ]);

        // ✅ ULTIMATE FIX: Auto-fix pickup service data
        $requestData = $request->all();
        
        // Auto-fix pickup service jika ada tapi tidak lengkap
        if (isset($requestData['pickup_service']) && is_array($requestData['pickup_service'])) {
            $pickup = &$requestData['pickup_service'];
            
            Log::info('🔧 AUTO-FIXING pickup service data', [
                'original_pickup' => $pickup
            ]);
            
            // Fix required fields dengan default values
            if (empty($pickup['service_name']) || trim($pickup['service_name']) === '') {
                $pickup['service_name'] = 'Layanan Pickup Standar';
                Log::info('🔧 Fixed service_name to default');
            }
            
            if (empty($pickup['jarak']) || $pickup['jarak'] <= 0) {
                $pickup['jarak'] = 1; // Default 1 km
                Log::info('🔧 Fixed jarak to 1 km');
            }
            
            if (empty($pickup['pickup_date'])) {
                $pickup['pickup_date'] = date('Y-m-d'); // Today
                Log::info('🔧 Fixed pickup_date to today');
            }
            
            if (empty($pickup['pickup_time']) || trim($pickup['pickup_time']) === '') {
                $pickup['pickup_time'] = '09:00'; // Default time
                Log::info('🔧 Fixed pickup_time to 09:00');
            }
            
            if (!isset($pickup['total_cost']) || $pickup['total_cost'] < 0) {
                $pickup['total_cost'] = 0; // Default free
                Log::info('🔧 Fixed total_cost to 0');
            }
            
            Log::info('✅ PICKUP SERVICE AUTO-FIXED', [
                'fixed_pickup' => $pickup
            ]);
        } else if (isset($requestData['pickup_service']) && !is_array($requestData['pickup_service'])) {
            // Remove invalid pickup service data
            Log::info('🚨 REMOVING INVALID pickup_service (not array)');
            unset($requestData['pickup_service']);
        }
        
        // Create new request with fixed data
        $cleanedRequest = new Request($requestData);
        $cleanedRequest->setMethod($request->getMethod());
        
        Log::info('🧹 CLEANED REQUEST DATA', [
            'has_pickup_service' => isset($requestData['pickup_service']),
            'final_keys' => array_keys($requestData)
        ]);

        // ✅ ENHANCED: Relaxed validation rules for pickup service
        $validator = Validator::make($cleanedRequest->all(), [
            // Customer validation (wajib)
            'customer.name' => 'required|string|max:255',
            'customer.phone' => 'required|string|max:20',
            'customer.id' => 'sometimes|integer|exists:pelanggans,id',
            
            // Payment validation (wajib)
            'payment.method' => 'required|in:tunai,qris,bayar-nanti',
            'payment.total_amount' => 'required|numeric|min:0',
            'payment.jumlah_bayar' => 'required|numeric|min:0',
            'payment.subtotal_layanan' => 'sometimes|numeric|min:0',
            'payment.biaya_pickup' => 'sometimes|numeric|min:0',
            'payment.notes' => 'sometimes|string|max:500',
            
            // Items validation (OPSIONAL - tidak wajib ada)
            'items' => 'sometimes|array',
            'items.*.layanan_id' => 'sometimes|integer|exists:layanans,id',
            'items.*.layanan_nama' => 'required|string|max:100',
            'items.*.kuantitas' => 'required|array',
            'items.*.kuantitas.*' => 'numeric|min:0',
            'items.*.harga_satuan' => 'sometimes|array',
            'items.*.subtotal' => 'required|numeric|min:0',
            'items.*.catatan' => 'sometimes|string|max:255',
            
            // ✅ PICKUP SERVICE: SEMUA FIELD OPTIONAL (tidak ada yang required)
            'pickup_service' => 'sometimes|array',
            'pickup_service.setting_id' => 'sometimes|integer',
            'pickup_service.service_name' => 'sometimes|string|max:100',
            'pickup_service.service_type' => 'sometimes|string|max:50',
            'pickup_service.jarak' => 'sometimes|numeric|min:0|max:100',
            'pickup_service.rentang' => 'sometimes|string|max:50',
            'pickup_service.pickup_date' => 'sometimes|date',
            'pickup_service.pickup_time' => 'sometimes|string|max:20',
            'pickup_service.special_instructions' => 'sometimes|string|max:500',
            'pickup_service.base_cost' => 'sometimes|numeric|min:0',
            'pickup_service.total_cost' => 'sometimes|numeric|min:0',
        ], [
            // Custom error messages
            'customer.name.required' => 'Nama customer wajib diisi',
            'customer.phone.required' => 'Nomor telepon customer wajib diisi',
            'payment.method.required' => 'Metode pembayaran wajib dipilih',
            'payment.method.in' => 'Metode pembayaran tidak valid',
            'payment.total_amount.required' => 'Total pembayaran wajib diisi',
            'payment.total_amount.min' => 'Total pembayaran tidak boleh negatif',
            'pickup_service.jarak.max' => 'Jarak maksimal adalah 100 km',
        ]);

        if ($validator->fails()) {
            Log::warning('VALIDATION FAILED', [
                'errors' => $validator->errors(),
                'request' => $cleanedRequest->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ FLEXIBLE: Validasi transaksi - pickup service BENAR-BENAR OPSIONAL
        $hasItems = !empty($cleanedRequest->items);
        $hasPickupService = !empty($cleanedRequest->pickup_service);
        $totalAmount = (float) ($cleanedRequest->payment['total_amount'] ?? 0);
        
        // Minimal harus ada items ATAU pickup ATAU total amount > 0
        if (!$hasItems && !$hasPickupService && $totalAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak valid - tidak ada layanan atau pembayaran yang diproses',
                'errors' => ['transaction' => ['Tambahkan layanan laundry atau setup pickup service, atau pastikan ada pembayaran yang valid']]
            ], 422);
        }
        
        Log::info('✅ TRANSACTION VALIDATION PASSED', [
            'has_items' => $hasItems,
            'has_pickup' => $hasPickupService,
            'total_amount' => $totalAmount,
            'transaction_type' => $hasItems && $hasPickupService ? 'combined' : ($hasItems ? 'laundry_only' : 'pickup_only')
        ]);

        DB::beginTransaction();

        try {
            // Create or find customer dengan enhanced logic
            $customerData = $cleanedRequest->customer;
            $customer = $this->handleCustomer($customerData);

            // Generate unique transaction code
            $kodeTransaksi = Transaksi::generateKodeTransaksi();

            // Extract payment data
            $paymentData = $cleanedRequest->payment;
            $metodePembayaran = $paymentData['method'];
            $totalAmount = (float) $paymentData['total_amount'];
            $jumlahBayar = (float) $paymentData['jumlah_bayar'];
            $subtotalLayanan = (float) ($paymentData['subtotal_layanan'] ?? 0);
            $biayaPickup = (float) ($paymentData['biaya_pickup'] ?? 0);

            // Validasi total amount
            if (abs($totalAmount - ($subtotalLayanan + $biayaPickup)) > 0.01) {
                Log::warning('TOTAL AMOUNT MISMATCH', [
                    'total_amount' => $totalAmount,
                    'subtotal_layanan' => $subtotalLayanan,
                    'biaya_pickup' => $biayaPickup,
                    'calculated' => $subtotalLayanan + $biayaPickup
                ]);
                // Don't throw error, just log warning
            }

            // Calculate kembalian
            $kembalian = $metodePembayaran === 'tunai' 
                ? max(0, $jumlahBayar - $totalAmount)
                : 0;

            // Validasi pembayaran tunai
            if ($metodePembayaran === 'tunai' && $jumlahBayar < $totalAmount) {
                throw new \Exception('Jumlah bayar tidak mencukupi untuk pembayaran tunai');
            }

            // Process pickup service data (OPSIONAL)
            $pickupService = $cleanedRequest->pickup_service ?? [];
            $hasPickupService = !empty($pickupService);

            // Determine transaction status
            $statusTransaksi = $metodePembayaran === 'bayar-nanti' ? 'pending' : 'sukses';

            // ✅ Get authenticated user data untuk kasir_name
            $currentUser = Auth::user();
            $kasirName = null;
            $kasirId = null;

            if ($currentUser) {
                // Cek apakah user adalah admin atau karyawan
                if (isset($currentUser->nama_admin)) {
                    // User adalah admin
                    $kasirName = $currentUser->nama_admin;
                    $kasirId = $currentUser->id;
                } elseif (isset($currentUser->nama_karyawan)) {
                    // User adalah karyawan
                    $kasirName = $currentUser->nama_karyawan;
                    $kasirId = $currentUser->id;
                } else {
                    // Fallback untuk struktur user lain
                    $kasirName = $currentUser->name ?? $currentUser->nama ?? 'Kasir';
                    $kasirId = $currentUser->id;
                }
            }

            Log::info('✅ KASIR INFO', [
                'kasir_id' => $kasirId,
                'kasir_name' => $kasirName,
                'user_type' => $currentUser ? get_class($currentUser) : 'not_authenticated'
            ]);

            // Enhanced transaction data
            $transaksiData = [
                'kode_transaksi' => $kodeTransaksi,
                'tanggal_transaksi' => now(),
                'customer_id' => $customer->id,
                'customer_name' => $customer->nama_pelanggan,
                'customer_phone' => $customer->telepon,
                'metode_pembayaran' => $metodePembayaran,
                'total_amount' => $totalAmount,
                'jumlah_bayar' => $jumlahBayar,
                'kembalian' => $kembalian,
                'subtotal_layanan' => $subtotalLayanan,
                'biaya_pickup' => $biayaPickup,
                'status_transaksi' => $statusTransaksi,
                'status_cucian' => $hasItems ? 'pending' : 'completed',
                'catatan' => $paymentData['notes'] ?? null,
                
                // ✅ KASIR DATA - Data kasir yang sedang login
                'created_by' => $kasirId,
                'kasir_id' => $kasirId,
                'kasir_name' => $kasirName ?? 'Kasir',
                
                // ✅ PICKUP SERVICE: Hanya isi jika ada data valid
                'has_pickup_service' => $hasPickupService,
                'pickup_setting_id' => $hasPickupService ? ($pickupService['setting_id'] ?? null) : null,
                'pickup_service_name' => $hasPickupService ? ($pickupService['service_name'] ?? null) : null,
                'pickup_service_type' => $hasPickupService ? ($pickupService['service_type'] ?? 'pickup_only') : null,
                'pickup_jarak' => $hasPickupService ? ($pickupService['jarak'] ?? null) : null,
                'pickup_rentang' => $hasPickupService ? ($pickupService['rentang'] ?? null) : null,
                'pickup_date' => $hasPickupService ? ($pickupService['pickup_date'] ?? null) : null,
                'pickup_time' => $hasPickupService ? ($pickupService['pickup_time'] ?? null) : null,
                'pickup_special_instructions' => $hasPickupService ? ($pickupService['special_instructions'] ?? null) : null,
                'pickup_base_cost' => $hasPickupService ? ($pickupService['base_cost'] ?? null) : null,
                'pickup_total_cost' => $hasPickupService ? ($pickupService['total_cost'] ?? null) : null,
            ];

            // Create main transaction
            $transaksi = Transaksi::create($transaksiData);

            Log::info('TRANSAKSI CREATED', [
                'id' => $transaksi->id,
                'kode' => $kodeTransaksi,
                'customer' => $customer->nama_pelanggan,
                'total' => $totalAmount,
                'kasir' => $kasirName,
                'has_pickup' => $hasPickupService,
                'has_items' => $hasItems,
                'transaction_type' => $this->determineTransactionType($hasItems, $hasPickupService)
            ]);

            // Create transaction details for laundry items (jika ada)
            if ($hasItems && !empty($cleanedRequest->items)) {
                foreach ($cleanedRequest->items as $item) {
                    $detailData = [
                        'transaksi_id' => $transaksi->id,
                        'layanan_id' => $item['layanan_id'] ?? null,
                        'layanan_nama' => $item['layanan_nama'],
                        'kuantitas' => json_encode($item['kuantitas']),
                        'harga_satuan' => json_encode($item['harga_satuan'] ?? []),
                        'subtotal' => (float) $item['subtotal'],
                        'catatan' => $item['catatan'] ?? null
                    ];
                    
                    TransaksiDetail::create($detailData);
                    
                    Log::debug('DETAIL CREATED', [
                        'transaksi_id' => $transaksi->id,
                        'layanan' => $item['layanan_nama'],
                        'subtotal' => $item['subtotal']
                    ]);
                }
            }

            // Create cash flow for successful payments
            if ($transaksi->status_transaksi === 'sukses') {
                $this->createCashFlow($transaksi);
            }

            DB::commit();

            // Enhanced response dengan pickup dan laundry data
            $responseData = [
                'id' => $transaksi->id,
                'kode_transaksi' => $transaksi->kode_transaksi,
                'customer_id' => $transaksi->customer_id,
                'customer_name' => $transaksi->customer_name,
                'customer_phone' => $transaksi->customer_phone,
                'total_amount' => (float) $transaksi->total_amount,
                'subtotal_layanan' => (float) $transaksi->subtotal_layanan,
                'biaya_pickup' => (float) $transaksi->biaya_pickup,
                'metode_pembayaran' => $transaksi->metode_pembayaran,
                'jumlah_bayar' => (float) $transaksi->jumlah_bayar,
                'kembalian' => (float) $transaksi->kembalian,
                'status_transaksi' => $transaksi->status_transaksi,
                'status_cucian' => $transaksi->status_cucian,
                'tanggal_transaksi' => $transaksi->tanggal_transaksi->format('Y-m-d H:i:s'),
                'items_count' => $transaksi->details()->count(),
                'notes' => $transaksi->catatan,
                'has_pickup_service' => (bool) $transaksi->has_pickup_service,
                'transaction_type' => $this->determineTransactionType($hasItems, $hasPickupService),
                
                // ✅ KASIR DATA untuk response
                'kasir_id' => $transaksi->kasir_id,
                'kasir_name' => $transaksi->kasir_name,
                'created_by' => $transaksi->created_by,
            ];

            // Add pickup service data if exists
            if ($hasPickupService) {
                $responseData['pickup_service'] = [
                    'setting_id' => $transaksi->pickup_setting_id,
                    'service_name' => $transaksi->pickup_service_name,
                    'service_type' => $transaksi->pickup_service_type,
                    'jarak' => (float) $transaksi->pickup_jarak,
                    'rentang' => $transaksi->pickup_rentang,
                    'pickup_date' => $transaksi->pickup_date,
                    'pickup_time' => $transaksi->pickup_time,
                    'special_instructions' => $transaksi->pickup_special_instructions,
                    'base_cost' => (float) $transaksi->pickup_base_cost,
                    'total_cost' => (float) $transaksi->pickup_total_cost,
                ];
            }

            // Add items data if exists
            if ($hasItems) {
                $responseData['items'] = $transaksi->details->map(function ($detail) {
                    return [
                        'id' => $detail->id,
                        'layanan_id' => $detail->layanan_id,
                        'layanan_nama' => $detail->layanan_nama,
                        'kuantitas' => json_decode($detail->kuantitas, true),
                        'harga_satuan' => json_decode($detail->harga_satuan, true),
                        'subtotal' => (float) $detail->subtotal,
                        'catatan' => $detail->catatan,
                    ];
                });
            }

            Log::info('TRANSAKSI SUCCESS', [
                'kode' => $kodeTransaksi,
                'type' => $responseData['transaction_type'],
                'total' => $totalAmount,
                'kasir' => $kasirName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => $responseData
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('STORE TRANSAKSI ERROR', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $cleanedRequest->all(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Enhanced index dengan filter pickup service
     */
    public function index(Request $request)
    {
        try {
            $query = Transaksi::with(['customer', 'details'])
                ->orderBy('tanggal_transaksi', 'desc');

            // Enhanced filtering
            if ($request->date_from && $request->date_to) {
                $query->whereBetween('tanggal_transaksi', [
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay()
                ]);
            }

            if ($request->payment_method) {
                $query->where('metode_pembayaran', $request->payment_method);
            }

            if ($request->status) {
                $query->where('status_transaksi', $request->status);
            }

            if ($request->laundry_status) {
                $query->where('status_cucian', $request->laundry_status);
            }

            // Filter berdasarkan jenis transaksi
            if ($request->transaction_type) {
                switch ($request->transaction_type) {
                    case 'laundry_only':
                        $query->where('has_pickup_service', false)
                              ->whereHas('details');
                        break;
                    case 'pickup_only':
                        $query->where('has_pickup_service', true)
                              ->whereDoesntHave('details');
                        break;
                    case 'combined':
                        $query->where('has_pickup_service', true)
                              ->whereHas('details');
                        break;
                }
            }

            // Filter pickup service
            if ($request->has('has_pickup')) {
                $query->where('has_pickup_service', (bool) $request->has_pickup);
            }

            // Search functionality
            if ($request->search) {
                $searchTerm = '%' . $request->search . '%';
                $query->where(function($q) use ($searchTerm) {
                    $q->where('customer_name', 'like', $searchTerm)
                      ->orWhere('customer_phone', 'like', $searchTerm)
                      ->orWhere('kode_transaksi', 'like', $searchTerm)
                      ->orWhere('pickup_service_name', 'like', $searchTerm);
                });
            }

            $perPage = min($request->per_page ?? 15, 100);
            $transaksi = $query->paginate($perPage);

            // Map data dengan service_type di top level untuk frontend
            $mappedData = collect($transaksi->items())->map(function ($tx) {
                $txArray = $tx instanceof \Illuminate\Database\Eloquent\Model ? $tx->toArray() : $tx;
                // Tambahkan service_type di top level jika ada pickup service
                if (isset($txArray['pickup_service_type'])) {
                    $txArray['service_type'] = $txArray['pickup_service_type'];
                }
                return $txArray;
            })->toArray();

            // Enhanced response dengan statistik
            $stats = [
                'total_transactions' => $transaksi->total(),
                'total_amount' => $query->sum('total_amount'),
                'laundry_only' => Transaksi::where('has_pickup_service', false)->whereHas('details')->count(),
                'pickup_only' => Transaksi::where('has_pickup_service', true)->whereDoesntHave('details')->count(),
                'combined' => Transaksi::where('has_pickup_service', true)->whereHas('details')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $mappedData,
                'pagination' => [
                    'current_page' => $transaksi->currentPage(),
                    'total_pages' => $transaksi->lastPage(),
                    'per_page' => $transaksi->perPage(),
                    'total' => $transaksi->total(),
                    'from' => $transaksi->firstItem(),
                    'to' => $transaksi->lastItem()
                ],
                'statistics' => $stats,
                'filters_applied' => $request->only([
                    'date_from', 'date_to', 'payment_method', 'status', 
                    'laundry_status', 'transaction_type', 'has_pickup', 'search'
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('INDEX TRANSAKSI ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data transaksi',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Enhanced show dengan detail pickup service
     */
    public function show($id)
    {
        try {
            $transaksi = Transaksi::with(['customer', 'details', 'cashFlow'])
                ->findOrFail($id);

            // Enhanced response dengan pickup service data lengkap
            $response = [
                'id' => $transaksi->id,
                'kode_transaksi' => $transaksi->kode_transaksi,
                'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                'customer' => [
                    'id' => $transaksi->customer_id,
                    'name' => $transaksi->customer_name,
                    'phone' => $transaksi->customer_phone,
                    'data' => $transaksi->customer
                ],
                'payment' => [
                    'method' => $transaksi->metode_pembayaran,
                    'total_amount' => (float) $transaksi->total_amount,
                    'subtotal_layanan' => (float) $transaksi->subtotal_layanan,
                    'biaya_pickup' => (float) $transaksi->biaya_pickup,
                    'jumlah_bayar' => (float) $transaksi->jumlah_bayar,
                    'kembalian' => (float) $transaksi->kembalian,
                ],
                'status' => [
                    'transaksi' => $transaksi->status_transaksi,
                    'cucian' => $transaksi->status_cucian,
                ],
                'details' => $transaksi->details,
                'cash_flow' => $transaksi->cashFlow,
                'has_pickup_service' => (bool) $transaksi->has_pickup_service,
                'transaction_type' => $this->determineTransactionType(
                    $transaksi->details()->exists(), 
                    $transaksi->has_pickup_service
                ),
                'catatan' => $transaksi->catatan,
                'created_by' => $transaksi->created_by,
                'created_at' => $transaksi->created_at,
                'updated_at' => $transaksi->updated_at,
            ];
            
            // Add pickup service data jika ada
            if ($transaksi->has_pickup_service) {
                $response['pickup_service'] = [
                    'setting_id' => $transaksi->pickup_setting_id,
                    'service_name' => $transaksi->pickup_service_name,
                    'service_type' => $transaksi->pickup_service_type,
                    'jarak' => (float) $transaksi->pickup_jarak,
                    'rentang' => $transaksi->pickup_rentang,
                    'pickup_date' => $transaksi->pickup_date,
                    'pickup_time' => $transaksi->pickup_time,
                    'special_instructions' => $transaksi->pickup_special_instructions,
                    'base_cost' => (float) $transaksi->pickup_base_cost,
                    'total_cost' => (float) $transaksi->pickup_total_cost,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error('SHOW TRANSAKSI ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail transaksi',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // =====================================
    // PRIVATE HELPER METHODS
    // =====================================

    /**
     * Handle customer creation or retrieval
     */
    private function handleCustomer($customerData)
    {
        try {
            // Jika ada customer ID, cari berdasarkan ID
            if (isset($customerData['id'])) {
                $customer = Pelanggan::find($customerData['id']);
                if ($customer) {
                    // Update nama jika berbeda
                    if ($customer->nama_pelanggan !== $customerData['name']) {
                        $customer->update(['nama_pelanggan' => $customerData['name']]);
                    }
                    return $customer;
                }
            }

            // Clean phone number
            $cleanPhone = preg_replace('/[^0-9]/', '', $customerData['phone']);
            
            // FirstOrCreate berdasarkan nomor telepon
            $customer = Pelanggan::firstOrCreate(
                ['telepon' => $cleanPhone],
                [
                    'nama_pelanggan' => $customerData['name'],
                    'alamat' => $customerData['address'] ?? null,
                    'created_at' => now()
                ]
            );

            // Update nama jika customer sudah ada tapi namanya berbeda
            if (!$customer->wasRecentlyCreated && $customer->nama_pelanggan !== $customerData['name']) {
                $customer->update(['nama_pelanggan' => $customerData['name']]);
            }

            return $customer;

        } catch (\Exception $e) {
            Log::error('HANDLE CUSTOMER ERROR: ' . $e->getMessage(), [
                'customer_data' => $customerData
            ]);
            throw new \Exception('Gagal memproses data customer: ' . $e->getMessage());
        }
    }

    /**
     * Determine transaction type based on services
     */
    private function determineTransactionType($hasItems, $hasPickupService)
    {
        if ($hasItems && $hasPickupService) {
            return 'combined';
        } elseif ($hasItems && !$hasPickupService) {
            return 'laundry_only';
        } elseif (!$hasItems && $hasPickupService) {
            return 'pickup_only';
        }
        
        return 'unknown';
    }

    /**
     * Create cash flow entry untuk transaksi sukses
     */
    private function createCashFlow($transaksi)
    {
        try {
            if ($transaksi->status_transaksi !== 'sukses') {
                return;
            }

            // Check jika cash flow sudah ada
            $existingCashFlow = CashFlow::where('transaksi_id', $transaksi->id)->first();
            if ($existingCashFlow) {
                Log::info('CASH FLOW ALREADY EXISTS', ['transaksi_id' => $transaksi->id]);
                return;
            }

            $cashFlowData = [
                'transaksi_id' => $transaksi->id,
                'tanggal' => $transaksi->tanggal_transaksi->format('Y-m-d'),
                'jenis_pembayaran' => $transaksi->metode_pembayaran,
                'amount' => $transaksi->total_amount,
                'tipe' => 'income',
                'kategori' => $this->determineCashFlowCategory($transaksi),
                'deskripsi' => $this->generateCashFlowDescription($transaksi),
                'created_at' => now(),
                'updated_at' => now()
            ];

            CashFlow::create($cashFlowData);

            Log::info('CASH FLOW CREATED', [
                'transaksi_id' => $transaksi->id,
                'amount' => $transaksi->total_amount,
                'kategori' => $cashFlowData['kategori']
            ]);

        } catch (\Exception $e) {
            Log::error('CREATE CASH FLOW ERROR: ' . $e->getMessage(), [
                'transaksi_id' => $transaksi->id ?? 'unknown'
            ]);
            // Don't throw exception karena cash flow bukan critical untuk transaksi
        }
    }

    /**
     * Determine cash flow category based on transaction type
     */
    private function determineCashFlowCategory($transaksi)
    {
        $hasItems = $transaksi->details()->exists() || $transaksi->subtotal_layanan > 0;
        $hasPickup = $transaksi->has_pickup_service;

        if ($hasItems && $hasPickup) {
            return 'laundry_pickup_combined';
        } elseif ($hasItems) {
            return 'laundry_service';
        } elseif ($hasPickup) {
            return 'pickup_service';
        }

        return 'other_service';
    }

    /**
     * Generate descriptive cash flow description
     */
    private function generateCashFlowDescription($transaksi)
    {
        $parts = [];
        $parts[] = "Pembayaran {$transaksi->metode_pembayaran}";
        
        if ($transaksi->subtotal_layanan > 0) {
            $parts[] = "layanan laundry (Rp " . number_format($transaksi->subtotal_layanan, 0, ',', '.') . ")";
        }
        
        if ($transaksi->has_pickup_service && $transaksi->biaya_pickup > 0) {
            $parts[] = "pickup service (Rp " . number_format($transaksi->biaya_pickup, 0, ',', '.') . ")";
        }
        
        $parts[] = "transaksi {$transaksi->kode_transaksi}";
        $parts[] = "customer {$transaksi->customer_name}";

        return implode(' untuk ', $parts);
    }

    /**
     * Update status transaksi dengan support pickup status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_transaksi' => 'sometimes|in:pending,sukses,cancelled',
            'status_cucian' => 'sometimes|in:pending,processing,completed,delivered',
            'pickup_status' => 'sometimes|in:pending,dioutlet,diantar,selesai'
        ], [
            'status_transaksi.in' => 'Status transaksi tidak valid',
            'status_cucian.in' => 'Status cucian tidak valid',
            'pickup_status.in' => 'Status pickup tidak valid'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transaksi = Transaksi::findOrFail($id);
            $oldStatusTransaksi = $transaksi->status_transaksi;
            $oldStatusCucian = $transaksi->status_cucian;

            DB::beginTransaction();

            // Update status yang diberikan
            $updateData = array_filter([
                'status_transaksi' => $request->status_transaksi,
                'status_cucian' => $request->status_cucian,
                'pickup_status' => $request->pickup_status
            ]);

            $transaksi->update($updateData);

            // Handle payment status changes
            if ($request->status_transaksi === 'sukses' && $oldStatusTransaksi !== 'sukses') {
                $this->createCashFlow($transaksi);
            }

            if ($request->status_transaksi === 'cancelled') {
                CashFlow::where('transaksi_id', $transaksi->id)->delete();
            }

            DB::commit();

            Log::info('STATUS UPDATED', [
                'transaksi_id' => $id,
                'old_status' => [
                    'transaksi' => $oldStatusTransaksi,
                    'cucian' => $oldStatusCucian
                ],
                'new_status' => [
                    'transaksi' => $transaksi->status_transaksi,
                    'cucian' => $transaksi->status_cucian
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status berhasil diupdate',
                'data' => [
                    'id' => $transaksi->id,
                    'status_transaksi' => $transaksi->status_transaksi,
                    'status_cucian' => $transaksi->status_cucian,
                    'updated_at' => $transaksi->fresh()->updated_at
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('UPDATE STATUS ERROR: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete transaksi dengan cleanup lengkap
     */
    public function destroy($id)
    {
        try {
            $transaksi = Transaksi::findOrFail($id);

            DB::beginTransaction();

            Log::warning('DELETING TRANSAKSI', [
                'id' => $id,
                'kode' => $transaksi->kode_transaksi,
                'customer' => $transaksi->customer_name,
                'total' => $transaksi->total_amount,
                'has_pickup' => $transaksi->has_pickup_service
            ]);

            // Delete related records
            CashFlow::where('transaksi_id', $id)->delete();
            TransaksiDetail::where('transaksi_id', $id)->delete();
            
            // Delete main transaction
            $transaksi->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dihapus'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('DELETE TRANSAKSI ERROR: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enhanced laporan harian dengan breakdown pickup service
     */
    public function laporanHarian(Request $request)
    {
        try {
            $tanggal = $request->input('tanggal') ?? now()->toDateString();
            
            // Validasi tanggal
            try {
                $tanggalObj = Carbon::parse($tanggal);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format tanggal tidak valid'
                ], 422);
            }

            $startDate = $tanggalObj->startOfDay();
            $endDate = $tanggalObj->copy()->endOfDay();

            // Get cash flow data
            $cashFlow = CashFlow::with('transaksi')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->get();

            // Get transactions data
            $transaksi = Transaksi::whereBetween('tanggal_transaksi', [$startDate, $endDate])
                ->where('status_transaksi', 'sukses')
                ->get();

            // Enhanced summary dengan breakdown detail
            $summary = [
                'date' => $tanggal,
                'total_income' => $cashFlow->where('tipe', 'income')->sum('amount'),
                'total_outcome' => $cashFlow->where('tipe', 'outcome')->sum('amount'),
                'net_income' => $cashFlow->where('tipe', 'income')->sum('amount') - $cashFlow->where('tipe', 'outcome')->sum('amount'),
                'total_transactions' => $transaksi->count(),
                
                // Breakdown by payment method
                'payment_methods' => $cashFlow->where('tipe', 'income')
                    ->groupBy('jenis_pembayaran')
                    ->map(function ($items, $method) use ($cashFlow) {
                        $totalIncome = $cashFlow->where('tipe', 'income')->sum('amount');
                        return [
                            'method' => $method,
                            'count' => $items->count(),
                            'total' => $items->sum('amount'),
                            'percentage' => $totalIncome > 0 ? 
                                round(($items->sum('amount') / $totalIncome) * 100, 2) : 0
                        ];
                    })->values(),
                
                // Enhanced revenue breakdown
                'revenue_breakdown' => [
                    'laundry_only' => $transaksi->where('has_pickup_service', false)->sum('subtotal_layanan'),
                    'pickup_only' => $transaksi->where('has_pickup_service', true)
                        ->where('subtotal_layanan', 0)->sum('biaya_pickup'),
                    'combined' => $transaksi->where('has_pickup_service', true)
                        ->where('subtotal_layanan', '>', 0)->sum('total_amount'),
                    'total_laundry_revenue' => $transaksi->sum('subtotal_layanan'),
                    'total_pickup_revenue' => $transaksi->where('has_pickup_service', true)->sum('biaya_pickup'),
                ],
                
                // Transaction type breakdown
                'transaction_types' => [
                    'laundry_only' => [
                        'count' => $transaksi->where('has_pickup_service', false)->count(),
                        'revenue' => $transaksi->where('has_pickup_service', false)->sum('total_amount')
                    ],
                    'pickup_only' => [
                        'count' => $transaksi->where('has_pickup_service', true)
                            ->where('subtotal_layanan', 0)->count(),
                        'revenue' => $transaksi->where('has_pickup_service', true)
                            ->where('subtotal_layanan', 0)->sum('total_amount')
                    ],
                    'combined' => [
                        'count' => $transaksi->where('has_pickup_service', true)
                            ->where('subtotal_layanan', '>', 0)->count(),
                        'revenue' => $transaksi->where('has_pickup_service', true)
                            ->where('subtotal_layanan', '>', 0)->sum('total_amount')
                    ]
                ],
                
                // Status breakdown
                'status_breakdown' => [
                    'completed' => $transaksi->where('status_cucian', 'completed')->count(),
                    'processing' => $transaksi->where('status_cucian', 'processing')->count(),
                    'pending' => $transaksi->where('status_cucian', 'pending')->count(),
                ]
            ];

            // Top customers today
            $topCustomers = $transaksi->groupBy('customer_id')
                ->map(function ($customerTransactions) {
                    $first = $customerTransactions->first();
                    return [
                        'customer_name' => $first->customer_name,
                        'customer_phone' => $first->customer_phone,
                        'transaction_count' => $customerTransactions->count(),
                        'total_spent' => $customerTransactions->sum('total_amount')
                    ];
                })
                ->sortByDesc('total_spent')
                ->take(10)
                ->values();

            return response()->json([
                'success' => true,
                'data' => $cashFlow,
                'summary' => $summary,
                'top_customers' => $topCustomers,
                'date' => $tanggal,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d H:i:s'),
                    'end' => $endDate->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('LAPORAN HARIAN ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menggenerate laporan harian',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     */
    public function dashboardStats(Request $request)
    {
        try {
            $today = now();
            $yesterday = $today->copy()->subDay();
            $thisMonth = $today->copy()->startOfMonth();

            // Today's stats
            $todayStats = [
                'transactions' => Transaksi::whereDate('tanggal_transaksi', $today)
                    ->where('status_transaksi', 'sukses')->count(),
                'revenue' => Transaksi::whereDate('tanggal_transaksi', $today)
                    ->where('status_transaksi', 'sukses')->sum('total_amount'),
                'pickup_transactions' => Transaksi::whereDate('tanggal_transaksi', $today)
                    ->where('has_pickup_service', true)->where('status_transaksi', 'sukses')->count()
            ];

            // Yesterday comparison
            $yesterdayStats = [
                'transactions' => Transaksi::whereDate('tanggal_transaksi', $yesterday)
                    ->where('status_transaksi', 'sukses')->count(),
                'revenue' => Transaksi::whereDate('tanggal_transaksi', $yesterday)
                    ->where('status_transaksi', 'sukses')->sum('total_amount')
            ];

            // Monthly stats
            $monthlyStats = [
                'transactions' => Transaksi::whereBetween('tanggal_transaksi', 
                    [$thisMonth, $today])->where('status_transaksi', 'sukses')->count(),
                'revenue' => Transaksi::whereBetween('tanggal_transaksi', 
                    [$thisMonth, $today])->where('status_transaksi', 'sukses')->sum('total_amount')
            ];

            // Pending transactions
            $pendingStats = [
                'payment_pending' => Transaksi::where('status_transaksi', 'pending')->count(),
                'laundry_pending' => Transaksi::where('status_cucian', 'pending')->count(),
            ];

            // Growth calculations
            $growth = [
                'transactions_daily' => $yesterdayStats['transactions'] > 0 ? 
                    round((($todayStats['transactions'] - $yesterdayStats['transactions']) / $yesterdayStats['transactions']) * 100, 2) : 
                    ($todayStats['transactions'] > 0 ? 100 : 0),
                'revenue_daily' => $yesterdayStats['revenue'] > 0 ? 
                    round((($todayStats['revenue'] - $yesterdayStats['revenue']) / $yesterdayStats['revenue']) * 100, 2) : 
                    ($todayStats['revenue'] > 0 ? 100 : 0)
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'today' => $todayStats,
                    'yesterday' => $yesterdayStats,
                    'monthly' => $monthlyStats,
                    'pending' => $pendingStats,
                    'growth' => $growth,
                    'last_updated' => now()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('DASHBOARD STATS ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil statistik dashboard',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk update status untuk multiple transaksi
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'integer|exists:transaksi,id',
            'status_transaksi' => 'sometimes|in:pending,sukses,cancelled',
            'status_cucian' => 'sometimes|in:pending,processing,completed,delivered',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = array_filter([
                'status_transaksi' => $request->status_transaksi,
                'status_cucian' => $request->status_cucian,
                'updated_at' => now()
            ]);

            $affected = Transaksi::whereIn('id', $request->transaction_ids)
                ->update($updateData);

            // Handle cash flow untuk perubahan status pembayaran
            if ($request->status_transaksi) {
                foreach ($request->transaction_ids as $transaksiId) {
                    $transaksi = Transaksi::find($transaksiId);
                    if ($transaksi) {
                        if ($request->status_transaksi === 'sukses') {
                            $this->createCashFlow($transaksi);
                        } elseif ($request->status_transaksi === 'cancelled') {
                            CashFlow::where('transaksi_id', $transaksiId)->delete();
                        }
                    }
                }
            }

            DB::commit();

            Log::info('BULK STATUS UPDATE', [
                'transaction_count' => count($request->transaction_ids),
                'affected_rows' => $affected,
                'update_data' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => "Status {$affected} transaksi berhasil diupdate",
                'affected_count' => $affected
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('BULK UPDATE ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get payment updates since last check
     * Berguna untuk kasir mendeteksi pembayaran dari kurir
     */
    public function getPaymentUpdates(Request $request)
    {
        try {
            $sinceTimestamp = $request->since ? 
                Carbon::parse($request->since) : 
                now()->subMinutes(5); // Default 5 menit terakhir

            // Get orders yang sudah dibayar (status_transaksi = sukses) setelah timestamp
            $paidOrders = Transaksi::where('status_transaksi', 'sukses')
                ->where('updated_at', '>=', $sinceTimestamp)
                ->where('has_pickup_service', true) // Hanya order dengan kurir
                ->with('customer')
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($tx) {
                    return [
                        'id' => $tx->id,
                        'kode_transaksi' => $tx->kode_transaksi,
                        'customer_name' => $tx->customer_name,
                        'total_amount' => (float) $tx->total_amount,
                        'status_transaksi' => $tx->status_transaksi,
                        'metode_pembayaran' => $tx->metode_pembayaran,
                        'jumlah_bayar' => (float) $tx->jumlah_bayar,
                        'kembalian' => (float) $tx->kembalian,
                        'updated_at' => $tx->updated_at,
                        'pickup_status' => $tx->pickup_status,
                        'service_type' => $tx->pickup_service_type,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Payment updates retrieved',
                'data' => $paidOrders,
                'count' => $paidOrders->count(),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('GET PAYMENT UPDATES ERROR: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pembayaran',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
        