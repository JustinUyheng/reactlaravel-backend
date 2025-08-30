<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Test route to verify authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user/profile', function () {
        $user = auth()->user();
        $userData = $user->toArray();

        $userData['profile_picture_url'] = $user->profile_picture
        ? Storage::url($user->profile_picture)
        : null;

        return response()->json([
            'user' => $userData,
            'message' => 'Profile retrieved successfully'
        ]);
    });

    // Profile picture routes
    Route::prefix('profile')->group(function () {
        Route::post('picture/upload', [ProfileController::class, 'uploadProfilePicture']);
        Route::delete('picture', [ProfileController::class, 'deleteProfilePicture']);
        Route::get('picture/{userId}', [ProfileController::class, 'getProfilePicture']);
    });
});

// Admin routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('pending-vendors', [AdminController::class, 'getPendingVendors']);
        Route::post('vendors/{user}/approve', [AdminController::class, 'approveVendor']);
        Route::post('vendors/{user}/reject', [AdminController::class, 'rejectVendor']);
        Route::get('users', [AdminController::class,'getAllUsers']);
        Route::get('users/vendors', [AdminController::class,'getAllVendors']);
        Route::get('users/customers', [AdminController::class,'getAllCustomers']);
        Route::get('users/{id}', [AdminController::class, 'getUser']);
        Route::get('feedback', [FeedbackController::class, 'index']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Store routes
    Route::prefix('store')->group(function () {
        Route::get('/', [StoreController::class, 'index']);
        Route::post('/', [StoreController::class, 'store']);
        Route::put('/', [StoreController::class, 'update']);
    });

    // Product routes (vendor only)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    // Order routes (vendor only)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
        Route::get('/statistics', [OrderController::class, 'statistics']);
    });

    // Feedback routes
    Route::prefix('feedback')->group(function () {
        Route::post('/', [FeedbackController::class, 'store']);
        Route::get('/', [FeedbackController::class, 'index']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('stores')->group(function () {
        Route::post('create', [StoreController::class, 'store']);
        Route::get('vendor', [StoreController::class, 'index']);
        Route::get('', [StoreController::class, 'index']);
    });
});

Route::post('feedback', [FeedbackController::class, 'store']);