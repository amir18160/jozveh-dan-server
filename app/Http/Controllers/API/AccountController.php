<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends BaseController
{

    public function getAccountDetails(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }
        return $this->sendResponse($user, 'Account details retrieved successfully.');
    }


    public function updateAccountDetails(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'bio' => 'nullable|string|max:1000',
            'profile_image' => 'nullable|string|max:255',
        ]);

        $user->fill($validated);
        $user->save();

        return $this->sendResponse($user, 'Account details updated successfully.');
    }


    public function changePassword(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('The :attribute is incorrect.');
                }
            }],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return $this->sendResponse(null, 'Password changed successfully.');
    }


    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }


        $request->validate([
            'password' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('The :attribute is incorrect for account deletion.');
                }
            }],
        ]);


        try {

            $user->tokens()->delete();
            $user->delete();


            return $this->sendResponse(null, 'Account deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Account deletion failed.', ['error' => $e->getMessage()], 500);
        }
    }
}
