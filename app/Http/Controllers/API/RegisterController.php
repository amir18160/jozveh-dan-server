<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RegisterController extends BaseController
{

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required',
            'email'      => 'required|email',
            'password'   => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        $input             = $request->all();
        $input['password'] = bcrypt($input['password']);

        $user              = User::create($input);

        $success = [
            'token' => $user->createToken('MyApp')->plainTextToken,
            'name'  => $user->name,
        ];

        return $this->sendResponse($success, 'User registered successfully.');
    }


    public function login(Request $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->sendError('Unauthorized.', ['error' => 'Invalid credentials'], 401);
        }

        $user    = Auth::user();
        $success = [
            'token' => $user->createToken('jozveh-dan-api')->plainTextToken,
            'name'  => $user->name,
        ];

        return $this->sendResponse($success, 'User logged in successfully.');
    }
}
