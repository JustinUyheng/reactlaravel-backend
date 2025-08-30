<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Services\ProductService;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected $productService;
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

        /**
     * Get all products for a store
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

            $products = Product::where('store_id', $store->id)
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

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        try {
            // Debug: Log the incoming request
            \Log::info('Product creation request:', [
                'all_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'has_file' => $request->hasFile('image'),
            ]);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|in:buffet,budget_meals,budget_snacks,snacks,drinks',
                'price' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed:', $validator->errors()->toArray());
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

            $productData = [
                'store_id' => $store->id,
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'price' => $request->price,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = 'products/' . $store->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('', $filename, 'public');
                $productData['image_path'] = $path;
            }

            $product = Product::create($productData);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $product->load('store')
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Product creation error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a product
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category' => 'required|in:buffet,budget_meals,budget_snacks,snacks,drinks',
                'price' => 'required|numeric|min:0',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_available' => 'boolean',
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

            $product = Product::where('id', $id)
                ->where('store_id', $store->id)
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            $productData = [
                'name' => $request->name,
                'description' => $request->description,
                'category' => $request->category,
                'price' => $request->price,
                'is_available' => $request->is_available ?? $product->is_available,
            ];

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }

                $file = $request->file('image');
                $filename = 'products/' . $store->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('', $filename, 'public');
                $productData['image_path'] = $path;
            }

            $product->update($productData);

            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $product->load('store')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product
     */
    public function destroy($id)
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

            $product = Product::where('id', $id)
                ->where('store_id', $store->id)
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Delete image file
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createProduct(CreateProductRequest $request)
    {
        $userId = Auth::user()->id;

        $storeId = Store::where('user_id', $userId);

        $product = $this->productService->createProduct($request->all(), $storeId);

        return response()->json([
            'product' => $product
        ], 201);

    }

}
