<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Register a new customer. Every new customer is "regular" by default and
     * gets a member_id + one free first order. They can upgrade to premium later
     * from the Membership page.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $lastId = User::query()->max('id') + 1;
        $memberId = 'KK-' . str_pad((string) $lastId, 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'member_id' => $memberId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
            'membership' => 'regular',
            'free_first_order_used' => false,
        ]);

        $token = $user->createToken('kk-auth')->plainTextToken;

        return response()->json([
            'message' => "Welcome to Kings' Kitchen! Your member ID is {$memberId}. Your first order ships free.",
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /** Login with either email or member_id. */
    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'], // email OR member_id
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['login'])
            ->orWhere('member_id', $data['login'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Those credentials do not match our records.'], 422);
        }

        $token = $user->createToken('kk-auth')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);
        $user->update($data);
        return response()->json(['user' => $user]);
    }
}
