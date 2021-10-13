<?php

namespace App\Http\Controllers\API\V1;

use API;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AuthUserResource;
use App\Models\User;
use Auth;
use CurrentUser;
use DB;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function me()
    {
        $user = CurrentUser::get();

        return new AuthUserResource($user);
    }

    public function register(Request $request)
    {
        $attr = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed'
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $attr['name'],
                'password' => bcrypt($attr['password']),
                'email' => $attr['email']
            ]);

            $token = $user->createToken('API Token')->plainTextToken;

            DB::commit();

            return API::response200([
                'data' => [
                    'token' => $token,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(Request $request)
    {
        $attr = $request->validate([
            'email' => 'required|string|email|',
            'password' => 'required|string|min:6'
        ]);

        if (!Auth::attempt($attr)) {
            return API::response401([], 'Credentials not match');
        }

        return API::response200([
            'data' => [
                'token' => CurrentUser::get()->createToken('API Token')->plainTextToken,
            ]
        ]);
    }

    public function logout()
    {
        CurrentUser::get()->tokens()->delete();

        return API::response200([], 'Token removed');
    }
}
