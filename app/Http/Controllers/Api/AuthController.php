<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_slug' => ['required', 'string', 'exists:tenants,slug'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role_slug' => ['nullable', 'string'],
        ]);

        $tenant = Tenant::query()->where('slug', $data['tenant_slug'])->firstOrFail();

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $data['role_slug'] ?? 'production-operator')
            ->first();

        if ($role) {
            $user->roles()->attach($role->id);
        }

        return response()->json([
            'token' => $user->createToken('iso-forge-api')->plainTextToken,
            'user' => $user->load(['tenant', 'roles']),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::query()->where('email', $credentials['email'])->firstOrFail();

        return response()->json([
            'token' => $user->createToken('iso-forge-api')->plainTextToken,
            'user' => $user->load(['tenant', 'roles.permissions']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
