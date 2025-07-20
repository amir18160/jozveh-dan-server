<?php

namespace App\Http\Controllers\API;

use App\Models\Review;
use App\Models\Resource; // For checking resource existence
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ReviewController extends BaseController
{

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'resource_id' => 'required|exists:resources,id',
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'all'])],
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $query = Review::with(['user:id,name,profile_image'])
            ->where('resource_id', $request->resource_id);

        $user = Auth::guard('sanctum')->user();

        if ($user && $user->role === 'admin' && $request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else if (!$user || $user->role !== 'admin') { // Non-admins or admins not filtering see only approved
            $query->where('status', 'approved');
        }

        $reviews = $query->latest()->paginate($request->get('per_page', 15));
        return $this->sendResponse($reviews, 'Reviews retrieved successfully.');
    }


    public function adminIndex(Request $request)
    {
        // This route should be protected by auth:sanctum and IsAdmin middleware
        Log::info("admin_index");

        $validator = Validator::make($request->all(), [
            'resource_id' => 'nullable|exists:resources,id',
            'user_id' => 'nullable|exists:users,id',
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected', 'all'])],
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255' // Search in comments
        ]);



        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $query = Review::with(['user:id,name,profile_image', 'resource:id,title']);

        if ($request->filled('resource_id')) {
            $query->where('resource_id', $request->resource_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('comment', 'like', '%' . $request->search . '%');
        }

        $reviews = $query->latest()->paginate($request->get('per_page', 15));
        return $this->sendResponse($reviews, 'All reviews retrieved successfully for admin.');
    }



    public function store(Request $request)
    {
        $user = Auth::user(); // Requires auth:sanctum middleware

        $validator = Validator::make($request->all(), [
            'resource_id' => 'required|exists:resources,id',
            'comment'     => 'required|string|min:10|max:5000',
            'rating'      => ['required', Rule::in(['liked', 'disliked', 'neutral'])],
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $existingReview = Review::where('user_id', $user->id)
            ->where('resource_id', $request->resource_id)
            ->first();

        if ($existingReview) {
            return $this->sendError('You have already reviewed this resource.', [], 409);
        }

        $review = Review::create([
            'user_id'     => $user->id,
            'resource_id' => $request->resource_id,
            'comment'     => $request->comment,
            'rating'      => $request->rating,
            'status'      => 'pending',
        ]);

        $review->load('user:id,name,profile_image');
        return $this->sendResponse($review, 'Review submitted and is pending approval.', 201);
    }


    public function show(Review $review)
    {
        $user = Auth::guard('sanctum')->user();

        if ($review->status === 'approved') {
            $review->load('user:id,name,profile_image', 'resource:id,title');
            return $this->sendResponse($review, 'Review retrieved successfully.');
        }

        if ($user && ($user->role === 'admin' || $user->id === $review->user_id)) {
            $review->load('user:id,name,profile_image', 'resource:id,title');
            return $this->sendResponse($review, 'Review retrieved successfully.');
        }

        return $this->sendError('Review not found or access denied.', [], 404);
    }

    public function update(Request $request, Review $review)
    {
        $user = Auth::user(); // Requires auth:sanctum middleware

        $isAdmin = $user->role === 'admin';
        $isOwner = $user->id === $review->user_id;

        if (!$isAdmin && !$isOwner) {
            return $this->sendError('Forbidden. You cannot update this review.', [], 403);
        }

        $rules = [];
        if ($isAdmin) {
            // Admins can update anything
            $rules = [
                'comment' => 'sometimes|required|string|min:10|max:5000',
                'rating'  => ['sometimes', 'required', Rule::in(['liked', 'disliked', 'neutral'])],
                'status'  => ['sometimes', 'required', Rule::in(['pending', 'approved', 'rejected'])],
            ];
        } elseif ($isOwner) {
            $rules = [
                'comment' => 'sometimes|required|string|min:10|max:5000',
                'rating'  => ['sometimes', 'required', Rule::in(['liked', 'disliked', 'neutral'])],
            ];
            if ($request->has('status')) {
                return $this->sendError('You are not authorized to change the review status.', [], 403);
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $review->update($validator->validated());


        $review->load('user:id,name,profile_image');
        return $this->sendResponse($review, 'Review updated successfully.');
    }


    public function destroy(Review $review)
    {
        $review->delete();
        return $this->sendResponse(null, 'Review deleted successfully.', 200);
    }
}
