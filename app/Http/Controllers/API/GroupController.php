<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class GroupController extends BaseController
{

    public function index(Request $request)
    {
        $query = Group::with('owner:id,name')->withCount('messages');

        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $groups = $query->latest()->paginate($request->get('per_page', 20));
        return $this->sendResponse($groups, 'Groups retrieved successfully.');
    }


    public function myGroups(Request $request)
    {
        $user = Auth::user();
        $query = Group::where('owner_id', $user->id)->withCount('messages');

        $groups = $query->latest()->paginate($request->get('per_page', 20));
        return $this->sendResponse($groups, 'Your groups retrieved successfully.');
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255|unique:groups,title',
            'description' => 'nullable|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('groups', 'public');
        }

        $group = Group::create([
            'title'       => $request->title,
            'description' => $request->description,
            'image_path'  => $imagePath,
            'owner_id'    => Auth::id(),
        ]);

        $group->load('owner:id,name');

        return $this->sendResponse($group, 'Group created successfully.', 201);
    }


    public function show(Group $group)
    {
        $group->load(['owner:id,name,profile_image'])->loadCount('messages');
        return $this->sendResponse($group, 'Group retrieved successfully.');
    }


    public function update(Request $request, Group $group)
    {
        $user = Auth::user();
        if ($user->id !== $group->owner_id && $user->role !== 'admin') {
            return $this->sendError('Forbidden. You do not own this group.', [], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255|unique:groups,title,' . $group->id,
            'description' => 'nullable|string|max:1000',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'clear_image' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors()->toArray(), 422);
        }

        $group->fill($request->only(['title', 'description']));

        if ($request->boolean('clear_image') && $group->image_path) {
            Storage::disk('public')->delete($group->image_path);
            $group->image_path = null;
        } elseif ($request->hasFile('image')) {
            if ($group->image_path) {
                Storage::disk('public')->delete($group->image_path);
            }
            $group->image_path = $request->file('image')->store('groups', 'public');
        }

        $group->save();
        $group->load('owner:id,name');

        return $this->sendResponse($group, 'Group updated successfully.');
    }


    public function destroy(Group $group)
    {
        $user = Auth::user();
        // Authorization: Only owner or admin can delete
        if ($user->id !== $group->owner_id && $user->role !== 'admin') {
            return $this->sendError('Forbidden. You do not own this group.', [], 403);
        }

        if ($group->image_path) {
            Storage::disk('public')->delete($group->image_path);
        }

        $group->delete();
        return $this->sendResponse(null, 'Group deleted successfully.');
    }
}
