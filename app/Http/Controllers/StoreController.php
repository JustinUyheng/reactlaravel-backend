<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
     /**
     * Get all stores (for customers to browse)
     */
    public function getAllStores(Request $request)
    {
        try {
            $stores = Store::with('user')
                ->whereHas('user', function($query) {
                    $query->where('is_approved', true); // Only show approved vendors
                })
                ->orderBy('business_name')
                ->get()
                ->map(function ($store) {
                    // Add store image URL
                    $store->store_image_url = $store->store_image 
                        ? Storage::url($store->store_image)
                        : null;
                    return $store;
                });

            return response()->json([
                'success' => true,
                'stores' => $stores
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving stores: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Get store information for the authenticated vendor
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

            return response()->json([
                'success' => true,
                'store' => $store
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving store: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new store
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string',
                'contact_number' => 'nullable|string|max:20',
                'operating_hours' => 'nullable|string',
                'store_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            
            // Check if user already has a store
            $existingStore = Store::where('user_id', $user->id)->first();
            if ($existingStore) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has a store'
                ], 400);
            }

            $storeData = [
                'user_id' => $user->id,
                'business_name' => $request->business_name,
                'business_type' => $request->business_type,
                'description' => $request->description,
                'address' => $request->address,
                'contact_number' => $request->contact_number,
                'operating_hours' => $request->operating_hours,
            ];

            // Handle store image upload
            if ($request->hasFile('store_image')) {
                $file = $request->file('store_image');
                $filename = 'stores/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('', $filename, 'public');
                $storeData['store_image'] = $path;
            }

            $store = Store::create($storeData);

            return response()->json([
                'success' => true,
                'message' => 'Store created successfully',
                'store' => $store
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating store: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update store information
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'business_name' => 'required|string|max:255',
                'business_type' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string',
                'contact_number' => 'nullable|string|max:20',
                'operating_hours' => 'nullable|string',
                'store_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

            $storeData = [
                'business_name' => $request->business_name,
                'business_type' => $request->business_type,
                'description' => $request->description,
                'address' => $request->address,
                'contact_number' => $request->contact_number,
                'operating_hours' => $request->operating_hours,
            ];

            // Handle store image upload
            if ($request->hasFile('store_image')) {
                // Delete old image if exists
                if ($store->store_image) {
                    Storage::disk('public')->delete($store->store_image);
                }

                $file = $request->file('store_image');
                $filename = 'stores/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('', $filename, 'public');
                $storeData['store_image'] = $path;
            }

            $store->update($storeData);

            return response()->json([
                'success' => true,
                'message' => 'Store updated successfully',
                'store' => $store->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating store: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific store by ID
     */
    public function show($id)
    {
        try {
            $store = Store::with('user')
                ->whereHas('user', function($query) {
                    $query->where('is_approved', true);
                })
                ->find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            // Add store image URL
            $store->store_image_url = $store->store_image 
                ? Storage::url($store->store_image)
                : null;

            return response()->json([
                'success' => true,
                'store' => $store
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving store: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get products for a specific store
     */
    public function getStoreProducts($id)
    {
        try {
            $store = Store::whereHas('user', function($query) {
                $query->where('is_approved', true);
            })->find($id);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found'
                ], 404);
            }

            $products = $store->products()
                ->where('is_available', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->map(function ($product) {
                    // Add image URL to each product
                    $product->image_url = $product->image_path 
                        ? Storage::url($product->image_path)
                        : null;
                    return $product;
                });

            return response()->json([
                'success' => true,
                'products' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving products: ' . $e->getMessage()
            ], 500);
        }
    }
}