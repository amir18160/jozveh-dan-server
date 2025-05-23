<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class GroupController extends BaseController
{
    public function index()
    {
        $groups = Group::latest()->paginate(10);
        return $this->sendResponse($groups, 'Groups retrieved successfully.');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('groups');
        }

        $group = Group::create([
            'title'       => $request->title,
            'description' => $request->description,
            'image_path'  => $imagePath,
        ]);

        return $this->sendResponse($group, 'Group created successfully.', 201);
    }

    public function show($id)
    {
        $group = Group::with('messages')->find($id);
        if (! $group) {
            return $this->sendError('Group not found.', [], 404);
        }

        return $this->sendResponse($group, 'Group retrieved successfully.');
    }

    public function update(Request $request, $id)
    {
        $group = Group::find($id);
        if (! $group) {
            return $this->sendError('Group not found.', [], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors(), 422);
        }

        if ($request->hasFile('image')) {
            if ($group->image_path) {
                Storage::delete($group->image_path);
            }
            $group->image_path = $request->file('image')->store('groups');
        }

        $group->fill($request->only(['title', 'description']));
        $group->save();

        return $this->sendResponse($group, 'Group updated successfully.');
    }

    public function destroy($id)
    {
        $group = Group::find($id);
        if (! $group) {
            return $this->sendError('Group not found.', [], 404);
        }

        if ($group->image_path) {
            Storage::delete($group->image_path);
        }

        $group->delete();
        return $this->sendResponse(null, 'Group deleted successfully.');
    }
}
