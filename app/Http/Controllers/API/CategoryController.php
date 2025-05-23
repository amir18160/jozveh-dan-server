<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\BaseController;
use Illuminate\Support\Facades\Validator;

class CategoryController extends BaseController
{
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return $this->sendResponse($categories, 'Categories retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories');
        }

        $category = Category::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'image_path' => $imagePath,
        ]);

        return $this->sendResponse($category, 'Category created successfully.', 201);
    }

    public function show($id)
    {
        $category = Category::with(['children', 'resources'])->find($id);
        if (!$category) {
            return $this->sendError('Category not found.');
        }

        return $this->sendResponse($category, 'Category retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->sendError('Category not found.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id|not_in:' . $id,
            'image' => 'nullable|image|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        if ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::delete($category->image_path);
            }
            $category->image_path = $request->file('image')->store('categories');
        }

        $category->fill($request->only(['name', 'parent_id']));
        $category->save();

        return $this->sendResponse($category, 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return $this->sendError('Category not found.');
        }

        if ($category->image_path) {
            Storage::delete($category->image_path);
        }

        $category->delete();
        return $this->sendResponse(null, 'Category deleted successfully.');
    }
}
