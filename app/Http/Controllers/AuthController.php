<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Validation Error.",
                "errors" => $validator->errors()
            ], 422);
        }

        try {

            $user = User::where('email', $request->email)
                        ->first();

            if (! $user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    "success" => false,
                    "message" => "The provided credentials are incorrect.",
                    "errors" => [
                        'email' => ['The provided credentials are incorrect.'],
                    ]
                ], 422);
            }

            $user->tokens()->delete();

            unset($user->email_verified_at);
            unset($user->created_at);
            unset($user->updated_at);

            $user->token = $user->createToken('auth_token')->plainTextToken;
            $user->tokens()->latest()->first()->update([
                'expires_at' => now()->addDay(3),
            ]);


            return ApiResponse::success($user);

        } catch (\Throwable $th) {
            Log::error($th);

            return response()->json([
                "success" => false,
                "message" => "API Error.",
                "errors" => null
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Validation Error.",
                "errors" => $validator->errors()
            ], 422);
        }

        $token = \Illuminate\Support\Str::random(60);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'email' => $request->email,
                'token' => \Illuminate\Support\Facades\Hash::make($token),
                'created_at' => now()
            ]
        );

        $user = User::where('email', $request->email)->first();

        // In a real application, you might want to send an email with the token here.
        // For example: $user->sendPasswordResetNotification($token);
        // Since API routes don't always have a password.reset frontend route defined,
        // sending it manually or explicitly defining the route is safer.
        // For simplicity, we just trigger the built-in notification:
        try {
            $user->sendPasswordResetNotification($token);
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email: ' . $e->getMessage());
            return response()->json([
                "success" => false,
                "message" => "Failed to send reset email. Make sure mail settings are configured.",
            ], 500);
        }

        return response()->json([
            "success" => true,
            "message" => "Password reset link sent successfully.",
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email|exists:users,email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Validation Error.",
                "errors"  => $validator->errors()
            ], 422);
        }

        // Cek record token di DB
        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !\Illuminate\Support\Facades\Hash::check($request->token, $record->token)) {
            return response()->json([
                "success" => false,
                "message" => "Token reset password tidak valid atau telah kadaluwarsa."
            ], 400);
        }

        // Update password user
        $user = User::where('email', $request->email)->first();
        $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        $user->save();

        // Hapus token yang sudah digunakan
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            "success" => true,
            "message" => "Password berhasil diperbarui! Silakan login dengan password baru Anda."
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout'
        ], 200);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        // Update Name & Email
        $user->name = $request->name;
        $user->email = $request->email;

        // Update Password jika diisi
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user
        ], 200);
    }
}
