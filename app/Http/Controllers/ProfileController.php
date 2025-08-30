<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Upload profile picture
     */
    public function uploadProfilePicture(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = auth()->user();
            
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            // Generate unique filename
            $file = $request->file('profile_picture');
            $filename = 'profile_pictures/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Store the file
            $path = $file->storeAs('', $filename, 'public');

            // Update user's profile picture path
            $user->update(['profile_picture' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture uploaded successfully',
                'profile_picture_url' => Storage::url($path),
                'user' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading profile picture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete profile picture
     */
    public function deleteProfilePicture(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user->profile_picture) {
                return response()->json([
                    'success' => false,
                    'message' => 'No profile picture to delete'
                ], 404);
            }

            // Delete file from storage
            Storage::disk('public')->delete($user->profile_picture);

            // Remove from database
            $user->update(['profile_picture' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture deleted successfully',
                'user' => $user->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting profile picture: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get profile picture (can be used for public viewing)
     */
    public function getProfilePicture($userId)
    {
        try {
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $profilePictureUrl = $user->profile_picture 
                ? Storage::url($user->profile_picture) 
                : null;

            return response()->json([
                'success' => true,
                'profile_picture_url' => $profilePictureUrl,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile_picture_url' => $profilePictureUrl
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving profile picture: ' . $e->getMessage()
            ], 500);
        }
    }
}
