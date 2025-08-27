<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AdminOnly;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function __construct()
    {
        // Protect listing with AdminOnly; public POST can be routed without this
        $this->middleware(AdminOnly::class)->only('index');
    }
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

    public function index(Request $request)
    {
        $query = Feedback::with(['user:id,firstname,lastname,email', 'store:id,business_name']);

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->integer('store_id'));
        }
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', (int) $request->input('min_rating'));
        }
        if ($request->filled('max_rating')) {
            $query->where('rating', '<=', (int) $request->input('max_rating'));
        }

        $feedback = $query->orderByDesc('created_at')->paginate(
            perPage: (int) $request->input('per_page', 20)
        );

        return response()->json([
            'message' => 'Feedback retrieved successfully',
            'feedback' => $feedback
        ], 200);
    }
}