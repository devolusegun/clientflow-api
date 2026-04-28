<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and return an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'password'     => Hash::make($request->password),
            'company_name' => $request->company_name,
            'phone'        => $request->phone,
            'address'      => $request->address,
            'currency'     => $request->currency ?? 'USD',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful.',
            'user'    => $this->formatUser($user),
            'token'   => $token,
        ], 201);
    }

    /**
     * Authenticate an existing user and return an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Revoke all previous tokens (single-session policy)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user'    => $this->formatUser($user),
            'token'   => $token,
        ]);
    }

    /**
     * Revoke the current token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user'             => $this->formatUser($user),
            'total_revenue'    => $user->total_revenue,
            'total_outstanding'=> $user->total_outstanding,
        ]);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'company_name' => 'sometimes|nullable|string|max:255',
            'phone'        => 'sometimes|nullable|string|max:50',
            'address'      => 'sometimes|nullable|string|max:500',
            'currency'     => 'sometimes|string|in:USD,EUR,GBP,NGN,CAD,AUD',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Profile updated.',
            'user'    => $this->formatUser($request->user()->fresh()),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function formatUser(User $user): array
    {
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'email'        => $user->email,
            'company_name' => $user->company_name,
            'phone'        => $user->phone,
            'address'      => $user->address,
            'currency'     => $user->currency,
            'created_at'   => $user->created_at,
        ];
    }
}
