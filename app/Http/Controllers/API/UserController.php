<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends BaseController
{

    public function index(Request $request)
    {


        $perPage = $request->query('per_page', 10);
        $users = User::paginate($perPage);

        // Use sendResponse for consistency
        return $this->sendResponse($users, 'Users retrieved successfully.');
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => ['in:user,admin'],
            'profile_image' => 'nullable|string',
            'bio' => 'nullable|string',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);


        return $this->sendResponse($user, 'User created successfully.', 201);
    }


    public function show(string $id)
    {
        $user = User::findOrFail($id);
        // Add a success message
        return $this->sendResponse($user, 'User retrieved successfully.', 200);
    }


    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'sometimes|required|string|min:6',
            'role' => ['sometimes', 'in:user,admin'],
            'profile_image' => 'nullable|string',
            'bio' => 'nullable|string',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);


        return $this->sendResponse($user, 'User updated successfully.', 200);
    }


    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return $this->sendResponse(null, 'User deleted successfully.', 200);
    }
}
