<?php

namespace App\Http\Controllers\API;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ReviewController extends BaseController
{
    /**
     * GET /reviews
     * Optional filter: ?resource_id=123
     */
    public function index(Request $request)
    {
        $query = Review::with(['user', 'resource']);

        if ($request->has('resource_id')) {
            $query->where('resource_id', $request->get('resource_id'));
        }

        $reviews = $query->latest()->paginate(10);

        return $this->sendResponse($reviews, 'Reviews retrieved successfully.');
    }

    /**
     * POST /reviews
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'     => 'required|exists:users,id',
            'resource_id' => 'required|exists:resources,id',
            'comment'     => 'nullable|string',
            'status'      => 'in:pending,approved,rejected',
            'rating'      => 'in:liked,disliked,neutral',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $review = Review::create($validator->validated());

        return $this->sendResponse($review->load(['user', 'resource']), 'Review created successfully.', 201);
    }

    /**
     * GET /reviews/{id}
     */
    public function show($id)
    {
        $review = Review::with(['user', 'resource'])->find($id);
        if (! $review) {
            return $this->sendError('Review not found.', [], 404);
        }

        return $this->sendResponse($review, 'Review retrieved successfully.');
    }

    /**
     * PUT/PATCH /reviews/{id}
     */
    public function update(Request $request, $id)
    {
        $review = Review::find($id);
        if (! $review) {
            return $this->sendError('Review not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'comment'     => 'nullable|string',
            'status'      => 'in:pending,approved,rejected',
            'rating'      => 'in:liked,disliked,neutral',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $review->fill($validator->validated());
        $review->save();

        return $this->sendResponse($review->load(['user', 'resource']), 'Review updated successfully.');
    }

    /**
     * DELETE /reviews/{id}
     */
    public function destroy($id)
    {
        $review = Review::find($id);
        if (! $review) {
            return $this->sendError('Review not found.', [], 404);
        }

        $review->delete();

        return $this->sendResponse(null, 'Review deleted successfully.');
    }
}
