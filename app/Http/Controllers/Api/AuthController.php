<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\OtpMail;
use Carbon\Carbon;

class AuthController extends Controller
{
   public function createUser(Request $request)
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

    if (!in_array($user->role, ['admin', 'editor'])) {
        return response()->json(['message' => 'Unauthorized. Only admins and editors can access the dashboard.'], 403);
    }

    $otp = rand(100000, 999999);
    Cache::put('otp_' . $user->id, $otp, now()->addMinutes(10));

    Mail::to($user->email)->send(new OtpMail($otp));

    return response()->json([
        'message' => 'OTP sent successfully to your email',
        'user_id' => $user->id
    ]);
}

    public function verifyOtp(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required',
        'otp' => 'required|numeric'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = User::find($request->user_id);
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $cachedOtp = Cache::get('otp_' . $user->id);

    if (!$cachedOtp || $cachedOtp != $request->otp) {
        return response()->json(['message' => 'Invalid or expired OTP'], 401);
    }

    // OTP is valid, clear it
    Cache::forget('otp_' . $user->id);

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