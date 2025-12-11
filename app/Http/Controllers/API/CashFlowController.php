<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
// use Barryvdh\DomPDF\Facade\Pdf; // Uncomment after installing dompdf

class CashFlowController extends Controller
{
    /**
     * 📊 Laporan Harian (Original method - enhanced)
     * Endpoint: GET /api/laporan-harian
     */
    public function laporanHarian(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tanggal' => 'sometimes|date',
                'date_from' => 'sometimes|date',
                'date_to' => 'sometimes|date|after_or_equal:date_from'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Support both single date and date range
            if ($request->has('date_from') && $request->has('date_to')) {
                $dateFrom = $request->date_from;
                $dateTo = $request->date_to;
                
                $cashFlowData = CashFlow::with('transaksi')
                    ->whereBetween('tanggal', [$dateFrom, $dateTo])
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
                $transactions = Transaksi::with(['customer', 'details'])
                    ->whereBetween('tanggal_transaksi', [$dateFrom, $dateTo])
                    ->orderBy('tanggal_transaksi', 'desc')
                    ->get();
            } else {
                $tanggal = $request->input('tanggal') ?? now()->toDateString();
                
                $cashFlowData = CashFlow::with('transaksi')
                    ->whereDate('tanggal', $tanggal)
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
                $transactions = Transaksi::with(['customer', 'details'])
                    ->whereDate('tanggal_transaksi', $tanggal)
                    ->orderBy('tanggal_transaksi', 'desc')
                    ->get();
                    
                $dateFrom = $tanggal;
                $dateTo = $tanggal;
            }

            // Calculate summary statistics
            $summary = $this->calculateSummaryStatistics($transactions);

            return response()->json([
                'success' => true,
                'data' => $transactions->map(function ($transaksi) {
                    return [
                        'id' => $transaksi->id,
                        'kode_transaksi' => $transaksi->kode_transaksi,
                        'tanggal_transaksi' => $transaksi->tanggal_transaksi,
                        'customer_name' => $transaksi->customer_name,
                        'customer_phone' => $transaksi->customer_phone,
                        'details' => $transaksi->details->map(function ($detail) {
                            return [
                                'layanan_nama' => $detail->layanan_nama,
                                'kuantitas' => json_decode($detail->kuantitas, true),
                                'subtotal' => $detail->subtotal
                            ];
                        }),
                        'metode_pembayaran' => $transaksi->metode_pembayaran,
                        'total_amount' => $transaksi->total_amount,
                        'status_transaksi' => $transaksi->status_transaksi,
                        'status_cucian' => $transaksi->status_cucian,
                        'jumlah_bayar' => $transaksi->jumlah_bayar,
                        'kembalian' => $transaksi->kembalian,
                        'catatan' => $transaksi->catatan,
                        'created_by' => $transaksi->created_by
                    ];
                }),
                'summary' => $summary,
                'cash_flow' => $cashFlowData,
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Laporan Harian Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat laporan harian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📈 Calculate summary statistics
     */
    private function calculateSummaryStatistics($transactions)
    {
        $totalOrder = $transactions->count();
        $totalPemasukan = 0;
        $orderPending = 0;
        $orderPaid = 0;
        $pemasukanTunai = 0;
        $pemasukanQris = 0;
        $pemasukanBayarNanti = 0;
        $transaksiTunai = 0;
        $transaksiQris = 0;
        $transaksiBayarNanti = 0;

        foreach ($transactions as $transaksi) {
            $amount = (float) $transaksi->total_amount;
            $method = $transaksi->metode_pembayaran;
            $status = $transaksi->status_transaksi;

            if ($status === 'paid') {
                $totalPemasukan += $amount;
                $orderPaid++;
            } elseif ($status === 'pending') {
                $orderPending++;
            }

            switch ($method) {
                case 'tunai':
                    $transaksiTunai++;
                    if ($status === 'paid') {
                        $pemasukanTunai += $amount;
                    }
                    break;
                case 'qris':
                    $transaksiQris++;
                    if ($status === 'paid') {
                        $pemasukanQris += $amount;
                    }
                    break;
                case 'bayar-nanti':
                    $transaksiBayarNanti++;
                    if ($status === 'paid') {
                        $pemasukanBayarNanti += $amount;
                    }
                    break;
            }
        }

        return [
            'totalOrder' => $totalOrder,
            'totalPemasukan' => $totalPemasukan,
            'orderPending' => $orderPending,
            'orderPaid' => $orderPaid,
            'pemasukanTunai' => $pemasukanTunai,
            'pemasukanQris' => $pemasukanQris,
            'pemasukanBayarNanti' => $pemasukanBayarNanti,
            'transaksiTunai' => $transaksiTunai,
            'transaksiQris' => $transaksiQris,
            'transaksiBayarNanti' => $transaksiBayarNanti
        ];
    }

    /**
     * 📄 Export to PDF
     * Endpoint: GET /api/laporan/export/pdf
     */
    public function exportToPDF(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Get report data
            $reportRequest = new Request([
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            
            $reportData = $this->laporanHarian($reportRequest);
            $data = $reportData->getData();

            if (!$data->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data untuk export PDF'
                ], 500);
            }

            $pdfData = [
                'title' => 'Laporan Harian',
                'period' => $dateFrom . ' - ' . $dateTo,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'summary' => $data->summary,
                'transactions' => collect($data->data)->take(100), // Limit for PDF
                'cash_flow' => $data->cash_flow
            ];

            // TODO: Install dompdf package first
            // For now, return downloadable HTML file
            $htmlContent = view('laporan.laporan-harian-pdf', $pdfData)->render();
            
            return response($htmlContent)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', "attachment; filename=laporan-harian-{$dateFrom}-{$dateTo}.html");

            // After installing dompdf, uncomment this:
            // $pdf = Pdf::loadView('laporan.laporan-harian-pdf', $pdfData)
            //     ->setPaper('a4', 'portrait');
            // return $pdf->download("laporan-harian-{$dateFrom}-{$dateTo}.pdf");

        } catch (\Exception $e) {
            Log::error('Export PDF Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal export PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Export to Excel
     * Endpoint: GET /api/laporan/export/excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Get report data
            $reportRequest = new Request([
                'date_from' => $dateFrom,
                'date_to' => $dateTo
            ]);
            
            $reportData = $this->laporanHarian($reportRequest);
            $data = $reportData->getData();

            if (!$data->success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengambil data untuk export Excel'
                ], 500);
            }

            // Prepare Excel data
            $excelData = [
                'summary' => $data->summary,
                'transactions' => $data->data,
                'cash_flow' => $data->cash_flow,
                'period' => $data->period,
                'exported_at' => now()->toISOString()
            ];

            $filename = "laporan-harian-{$dateFrom}-{$dateTo}.xlsx";
            
            // For now return JSON (you can implement actual Excel export using Maatwebsite/Excel)
            return response()->json($excelData)
                ->header('Content-Disposition', "attachment; filename={$filename}")
                ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        } catch (\Exception $e) {
            Log::error('Export Excel Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal export Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Get cash flow summary by date range
     * Endpoint: GET /api/cash-flow/summary
     */
    public function getCashFlowSummary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date_from' => 'required|date',
                'date_to' => 'required|date|after_or_equal:date_from'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $dateFrom = $request->date_from;
            $dateTo = $request->date_to;

            // Get cash flow data
            $cashFlows = CashFlow::with('transaksi')
                ->whereBetween('tanggal', [$dateFrom, $dateTo])
                ->get();

            // Calculate summaries
            $totalIncome = $cashFlows->where('tipe', 'income')->sum('amount');
            $totalExpense = $cashFlows->where('tipe', 'expense')->sum('amount');
            $netCashFlow = $totalIncome - $totalExpense;

            // Group by payment method
            $byPaymentMethod = $cashFlows->where('tipe', 'income')
                ->groupBy('jenis_pembayaran')
                ->map(function ($group) {
                    return [
                        'total' => $group->sum('amount'),
                        'count' => $group->count()
                    ];
                });

            // Daily breakdown
            $dailyBreakdown = $cashFlows->groupBy(function ($item) {
                return Carbon::parse($item->tanggal)->format('Y-m-d');
            })->map(function ($group) {
                return [
                    'income' => $group->where('tipe', 'income')->sum('amount'),
                    'expense' => $group->where('tipe', 'expense')->sum('amount'),
                    'net' => $group->where('tipe', 'income')->sum('amount') - $group->where('tipe', 'expense')->sum('amount'),
                    'transactions' => $group->count()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ],
                    'summary' => [
                        'total_income' => $totalIncome,
                        'total_expense' => $totalExpense,
                        'net_cash_flow' => $netCashFlow,
                        'total_transactions' => $cashFlows->count()
                    ],
                    'by_payment_method' => $byPaymentMethod,
                    'daily_breakdown' => $dailyBreakdown,
                    'raw_data' => $cashFlows
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Cash Flow Summary Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat ringkasan cash flow: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 💰 Get revenue trends
     * Endpoint: GET /api/cash-flow/trends
     */
    public function getRevenueTrends(Request $request)
    {
        try {
            $period = $request->input('period', 'month'); // week, month, year
            $endDate = now();
            
            switch ($period) {
                case 'week':
                    $startDate = $endDate->copy()->subWeeks(4);
                    $groupBy = 'week';
                    break;
                case 'year':
                    $startDate = $endDate->copy()->subYears(2);
                    $groupBy = 'month';
                    break;
                default: // month
                    $startDate = $endDate->copy()->subMonths(12);
                    $groupBy = 'day';
                    break;
            }

            $cashFlows = CashFlow::where('tipe', 'income')
                ->whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get();

            $trends = $cashFlows->groupBy(function ($item) use ($groupBy) {
                $date = Carbon::parse($item->tanggal);
                switch ($groupBy) {
                    case 'week':
                        return $date->format('Y-W');
                    case 'month':
                        return $date->format('Y-m');
                    default: // day
                        return $date->format('Y-m-d');
                }
            })->map(function ($group, $key) {
                return [
                    'period' => $key,
                    'revenue' => $group->sum('amount'),
                    'transactions' => $group->count(),
                    'avg_transaction' => $group->count() > 0 ? round($group->sum('amount') / $group->count(), 2) : 0
                ];
            })->sortKeys();

            return response()->json([
                'success' => true,
                'data' => [
                    'period_type' => $period,
                    'date_range' => [
                        'from' => $startDate->format('Y-m-d'),
                        'to' => $endDate->format('Y-m-d')
                    ],
                    'trends' => $trends,
                    'total_revenue' => $cashFlows->sum('amount'),
                    'total_transactions' => $cashFlows->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Revenue Trends Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat trend revenue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Get payment method analytics
     * Endpoint: GET /api/cash-flow/payment-analytics
     */
    public function getPaymentMethodAnalytics(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from', now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));

            $paymentAnalytics = CashFlow::with('transaksi')
                ->where('tipe', 'income')
                ->whereBetween('tanggal', [$dateFrom, $dateTo])
                ->get()
                ->groupBy('jenis_pembayaran')
                ->map(function ($group, $method) {
                    $total = $group->sum('amount');
                    $count = $group->count();
                    return [
                        'method' => $method,
                        'total_amount' => $total,
                        'transaction_count' => $count,
                        'average_amount' => $count > 0 ? round($total / $count, 2) : 0,
                        'percentage' => 0 // Will be calculated below
                    ];
                });

            // Calculate percentages
            $grandTotal = $paymentAnalytics->sum('total_amount');
            if ($grandTotal > 0) {
                $paymentAnalytics = $paymentAnalytics->map(function ($item) use ($grandTotal) {
                    $item['percentage'] = round(($item['total_amount'] / $grandTotal) * 100, 2);
                    return $item;
                });
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ],
                    'analytics' => $paymentAnalytics,
                    'summary' => [
                        'total_revenue' => $grandTotal,
                        'total_transactions' => $paymentAnalytics->sum('transaction_count')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Analytics Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat analisis metode pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }
}