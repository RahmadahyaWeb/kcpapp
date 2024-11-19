<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('username', 'password'))) {
            $auth = Auth::user();

            $token = $auth->createToken($auth->name)->plainTextToken;
        }

        return response()->json([
            'data'      => [
                'name'      => $auth->name,
                'username'  => $auth->username, 
            ],
            'message'   => 'Authenticated',
            'token'     => $token
        ]); 
    }
}
