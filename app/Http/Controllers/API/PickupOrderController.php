<?php
// app/Http/Controllers/PickupOrderController.php

namespace App\Http\Controllers;

use App\Models\PickupOrder;
use Illuminate\Http\Request;

class PickupOrderController extends Controller
{
    public function getByStatus($status)
    {
        $orders = PickupOrder::with(['transaksi', 'transaksi.customer'])
            ->where('pickup_status', $status)
            ->orderBy('scheduled_pickup_time')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getTodaySchedule()
    {
        $orders = PickupOrder::with(['transaksi'])
            ->today()
            ->orderBy('scheduled_pickup_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function markAsPickedUp(Request $request, $id)
    {
        $pickup = PickupOrder::findOrFail($id);
        $pickup->markAsPickedUp($request->notes, $request->actual_time);

        return response()->json([
            'success' => true,
            'message' => 'Pickup berhasil diselesaikan',
            'data' => $pickup->fresh()
        ]);
    }

    public function markAsDelivered(Request $request, $id)
    {
        $pickup = PickupOrder::findOrFail($id);
        $pickup->markAsDelivered($request->notes, $request->actual_time);

        return response()->json([
            'success' => true,
            'message' => 'Delivery berhasil diselesaikan',
            'data' => $pickup->fresh()
        ]);
    }
}