<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
     * Create a new order
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'store_id' => 'required|exists:stores,id',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.type' => 'required|in:order,reserve',
                'items.*.price' => 'required|numeric|min:0',
                'delivery_address' => 'nullable|string|max:500',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            $store = Store::find($request->store_id);

            // Calculate total amount
            $total = 0;
            $orderItems = [];

            foreach ($request->items as $item) {
                $product = Product::where('id', $item['product_id'])
                    ->where('store_id', $store->id)
                    ->first();

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product not found or does not belong to this store'
                    ], 404);
                }

                if (!$product->is_available) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product is not available: {$product->name}"
                    ], 400);
                }

                $itemTotal = $product->price * $item['quantity'];
                $total += $itemTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'total' => $itemTotal,
                    'type' => $item['type'],
                    'product_name' => $item['product_name'],
                ];
            }

            // Start database transaction
            DB::beginTransaction();

            // Create the order
            // TODO: Update to use the type from the request
            $order = Order::create([
                'user_id' => $user->id,
                'store_id' => $store->id,
                'subtotal' => $total,
                'total' => $total,
                'status' => 'preparing',
                'delivery_address' => $request->delivery_address,
                'notes' => $request->notes,
                'type' => 'order',
                'payment_method' => 'cash',
            ]);

            // Create order items
            foreach ($orderItems as $index => $orderItemData) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $orderItemData['product_id'],
                    'quantity' => $orderItemData['quantity'],
                    'price' => $orderItemData['price'],
                    'total' => $orderItemData['total'],
                    'product_name' => $orderItemData['product_name'],
                    'subtotal' => $order->subtotal,
                ]);
            }

            DB::commit();

            // Load relationships for response
            $order->load(['user', 'items.product', 'store']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders for the authenticated user
     */
    public function userOrders(Request $request)
    {
        try {
            $user = auth()->user();

            $orders = Order::where('user_id', $user->id)
                ->with(['store', 'items.product'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'orders' => $orders
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user orders: ' . $e->getMessage()
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