<?php

namespace App\Http\Controllers\API;

use App\Models\Resource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class ResourceController extends BaseController
{
    public function index(Request $request)
    {
        $query = Resource::with('categories');

        // Optional filter by category_id
        if ($request->has('category_id')) {
            $categoryId = $request->get('category_id');
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
        }

        $resources = $query->latest()->paginate(10);

        return $this->sendResponse($resources, 'Resources retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file',
            'user_id' => 'required|exists:users,id',
            'format' => 'nullable|string|max:50',
            'status' => 'in:active,inactive',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('resources');
        }

        $resource = Resource::create([
            'title' => $request->title,
            'description' => $request->description,
            'file_path' => $filePath,
            'user_id' => $request->user_id,
            'format' => $request->format,
            'status' => $request->status ?? 'active',
        ]);

        if ($request->has('category_ids')) {
            $resource->categories()->sync($request->category_ids);
        }

        return $this->sendResponse($resource->load('categories'), 'Resource created successfully.', 201);
    }

    public function show($id)
    {
        $resource = Resource::with('categories')->find($id);

        if (!$resource) {
            return $this->sendError('Resource not found.');
        }

        $resource->increment('view_count');

        return $this->sendResponse($resource, 'Resource retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return $this->sendError('Resource not found.');
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'file' => 'nullable|file',
            'format' => 'nullable|string|max:50',
            'status' => 'in:active,inactive',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        if ($request->hasFile('file')) {
            if ($resource->file_path) {
                Storage::delete($resource->file_path);
            }
            $resource->file_path = $request->file('file')->store('resources');
        }

        $resource->fill($request->only(['title', 'description', 'format', 'status']));
        $resource->save();

        if ($request->has('category_ids')) {
            $resource->categories()->sync($request->category_ids);
        }

        return $this->sendResponse($resource->load('categories'), 'Resource updated successfully.');
    }

    public function destroy($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return $this->sendError('Resource not found.');
        }

        if ($resource->file_path) {
            Storage::delete($resource->file_path);
        }

        $resource->categories()->detach(); // Remove relationships
        $resource->delete();

        return $this->sendResponse(null, 'Resource deleted successfully.');
    }

    public function download($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return $this->sendError('Resource not found.');
        }

        if (!$resource->file_path || !Storage::exists($resource->file_path)) {
            return $this->sendError('File not found.');
        }

        $resource->increment('download_count');

        return Storage::download($resource->file_path);
    }
}
