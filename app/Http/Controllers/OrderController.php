<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Get all orders for a store
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $store = Store::where('user_id', $user->id)->first();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            $orders = Order::where('store_id', $store->id)
                ->with(['user', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:preparing,ready,picked_up,cancelled,delivered',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $store = Store::where('user_id', $user->id)->first();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            $order = Order::where('id', $id)
                ->where('store_id', $store->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            $order->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $order->load(['user', 'items.product'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        try {
            $user = auth()->user();
            $store = Store::where('user_id', $user->id)->first();

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            $totalOrders = Order::where('store_id', $store->id)->count();
            $pendingOrders = Order::where('store_id', $store->id)
                ->whereIn('status', ['preparing', 'ready'])
                ->count();
            $totalRevenue = Order::where('store_id', $store->id)
                ->where('status', '!=', 'cancelled')
                ->sum('total');

            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_orders' => $totalOrders,
                    'pending_orders' => $pendingOrders,
                    'total_revenue' => $totalRevenue,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
