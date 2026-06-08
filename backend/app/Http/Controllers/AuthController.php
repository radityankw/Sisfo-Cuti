<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validate Input (NIK & Password)
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Attempt Login
        if (!Auth::attempt($request->only('nik', 'password'))) {
            return response()->json([
                'message' => 'NIK atau password salah.',
            ], 401);
        }

        // 3. Generate Token
        $user = User::where('nik', $request->nik)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4. Return Response
        return response()->json([
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
    
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}