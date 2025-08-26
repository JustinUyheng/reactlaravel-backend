<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'store_id' => 'nullable|integer',
        ]);

        $feedback = Feedback::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'store_id' => $data['store_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback
        ], 201);
    }
}