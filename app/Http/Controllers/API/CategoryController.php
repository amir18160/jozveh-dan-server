<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\BaseController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends BaseController
{

    public function index(Request $request)
    {
        $query = Category::with('childrenRecursive') // Eager load all descendants
            ->whereNull('parent_id');

        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        $categories = $query->orderBy('name')->paginate($request->get('per_page', 25));

        return $this->sendResponse($categories, 'Categories retrieved successfully.');
    }


    public function allFlat(Request $request)
    {
        $query = Category::select(['id', 'name', 'parent_id'])->orderBy('name');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->get();
        return $this->sendResponse($categories, 'All flat categories retrieved successfully.');
    }


    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'image_path' => $imagePath,
        ]);

        return $this->sendResponse($category->load('childrenRecursive'), 'Category created successfully.', 201);
    }


    public function show(Category $category)
    {
        $category->load([
            'childrenRecursive',
            'parent:id,name',
            'resources' => function ($query) {
                $query->with('user:id,name')->select(['resources.id', 'resources.title', 'resources.user_id'])->limit(10);
            }
        ]);
        return $this->sendResponse($category, 'Category retrieved successfully.');
    }


    public function update(Request $request, Category $category)
    {

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) use ($category) {
                    $query->where('id', '!=', $category->id); // Cannot be self
                }),

                function ($attribute, $value, $fail) use ($category) {
                    if ($value) {
                        $node = Category::find($value);
                        while ($node) {
                            if ($node->id === $category->id) {
                                $fail('Cannot set parent to one of its own descendants.');
                                return;
                            }
                            $node = $node->parent;
                        }
                    }
                },
            ],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
            'clear_image' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $category->name = $request->input('name', $category->name);

        if ($request->has('parent_id')) {
            $category->parent_id = $request->input('parent_id') ?: null;
        }


        if ($request->boolean('clear_image') && $category->image_path) {
            Storage::disk('public')->delete($category->image_path);
            $category->image_path = null;
        } elseif ($request->hasFile('image')) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }
            $category->image_path = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return $this->sendResponse($category->load('childrenRecursive'), 'Category updated successfully.');
    }


    public function destroy(Category $category)
    {


        if ($category->children()->count() > 0) {
            $newParentId = $category->parent_id; // Could be null
            $category->children()->update(['parent_id' => $newParentId]);
        }


        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }


        $category->delete();
        return $this->sendResponse(null, 'Category deleted successfully.', 200);
    }
}
