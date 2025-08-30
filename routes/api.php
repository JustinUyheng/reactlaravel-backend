<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
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
    Route::prefix('stores')->group(function () {
        Route::post('create', [StoreController::class, 'createStore']);
        Route::get('vendor', [StoreController::class, 'getVendorStore']);
        Route::get('', [StoreController::class, 'index']);
    });
});

Route::post('feedback', [FeedbackController::class, 'store']);