<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
   public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|min:6',
        'role' => 'nullable|in:admin,editor,user'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role ?? 'user'
    ]);

    return response()->json([
        'message' => 'User created successfully'
    ]);
}


   public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();

    if(!$user || !Hash::check($request->password, $user->password)){
        return response()->json(['message'=>'Invalid credentials'],401);
    }

    $tokenResult = $user->createToken('api-token');
    $plainTextToken = $tokenResult->plainTextToken;

    return response()->json([
        'token' => $plainTextToken,
        'expires_at' => now()->addMinutes(config('sanctum.expiration'))
    ]);
}
    public function logout(Request $request)
{
    // Supprime le token qui a été utilisé pour l'auth
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logout successful']);
}
}