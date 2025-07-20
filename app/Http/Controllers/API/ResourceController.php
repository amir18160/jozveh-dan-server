<?php

namespace App\Http\Controllers\API;

use App\Models\Resource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str; // Import Str facade

class ResourceController extends BaseController
{
    /**
     * Display a listing of the resources.
     * Publicly accessible.
     * Optional filters: ?category_id=X, ?user_id=Y, ?search=term, ?per_page=Z
     */
    public function index(Request $request)
    {
        $query = Resource::with(['user:id,name,profile_image', 'categories:id,name']); // Eager load user and categories

        if ($request->has('category_id')) {
            $categoryId = $request->get('category_id');
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        $resources = $query->latest()->paginate($request->get('per_page', 15));

        return $this->sendResponse($resources, 'Resources retrieved successfully.');
    }


    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:20480', // Max 20MB, adjust as needed. e.g. mimes:jpeg,png,pdf,mp4
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'sometimes|required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $filePath = null;
        $fileFormat = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('resources/user_' . $user->id, 'public');
            $fileFormat = $file->getClientOriginalExtension();
        }

        $resource = Resource::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $filePath,
            'user_id' => $user->id,
            'format' => $fileFormat,
            'status' => $request->input('status', 'active'),
            'view_count' => 0,
            'download_count' => 0,
        ]);

        if ($request->has('category_ids') && is_array($request->category_ids)) {
            $resource->categories()->sync($request->category_ids);
        }

        $resource->load(['user:id,name,profile_image', 'categories:id,name']);

        return $this->sendResponse($resource, 'Resource created successfully.', 201);
    }


    public function show(Resource $resource)
    {
        if (!$resource) {
            return $this->sendError('Resource not found.', [], 404);
        }
        $resource->increment('view_count');
        $resource->load(['user:id,name,profile_image', 'categories:id,name']);

        return $this->sendResponse($resource, 'Resource retrieved successfully.');
    }


    public function update(Request $request, Resource $resource)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        if ($user->id !== $resource->user_id && $user->role !== 'admin') {
            return $this->sendError('Forbidden. You do not have permission to update this resource.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'file' => 'nullable|file|max:20480',
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'sometimes|required|exists:categories,id',
            'clear_file' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $resource->fill($request->only(['title', 'description', 'status']));

        if ($request->boolean('clear_file') && $resource->file_path) {
            Storage::disk('public')->delete($resource->file_path);
            $resource->file_path = null;
            $resource->format = null;
        } elseif ($request->hasFile('file')) {
            if ($resource->file_path) {
                Storage::disk('public')->delete($resource->file_path);
            }
            $file = $request->file('file');
            $resource->file_path = $file->store('resources/user_' . $resource->user_id, 'public');
            $resource->format = $file->getClientOriginalExtension();
        }

        $resource->save();

        if ($request->has('category_ids')) {
            $resource->categories()->sync($request->input('category_ids', []));
        }

        $resource->load(['user:id,name,profile_image', 'categories:id,name']);

        return $this->sendResponse($resource, 'Resource updated successfully.');
    }


    public function destroy(Resource $resource)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        if ($user->id !== $resource->user_id && $user->role !== 'admin') {
            return $this->sendError('Forbidden. You do not have permission to delete this resource.', [], 403);
        }

        if ($resource->file_path) {
            Storage::disk('public')->delete($resource->file_path);
        }

        $resource->categories()->detach(); // Detach all categories
        $resource->delete();

        return $this->sendResponse(null, 'Resource deleted successfully.', 200);
    }


    public function download($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return $this->sendError('Resource not found.', [], 404);
        }

        if (!$resource->file_path || !Storage::disk('public')->exists($resource->file_path)) {
            return $this->sendError('File associated with this resource not found.', [], 404);
        }

        $resource->increment('download_count');

        $originalName = pathinfo(storage_path('app/public/' . $resource->file_path), PATHINFO_FILENAME);
        $extension = $resource->format ?: pathinfo($resource->file_path, PATHINFO_EXTENSION);
        $downloadName = Str::slug($resource->title ?: $originalName) . '.' . $extension;

        return Storage::disk('public')->download($resource->file_path, $downloadName);
    }


    public function myResources(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        $query = Resource::where('user_id', $user->id)
            ->with(['user:id,name,profile_image', 'categories:id,name']);

        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->has('category_id')) {
            $categoryId = $request->get('category_id');
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }


        $resources = $query->latest()->paginate($request->get('per_page', 15));

        return $this->sendResponse($resources, 'Your resources retrieved successfully.');
    }
}
