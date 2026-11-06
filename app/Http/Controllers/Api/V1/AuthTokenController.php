<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthTokenController extends Controller
{
    use ApiResponse;

    /**
     * Otentikasi pustakawan dan penerbitan Personal Access Token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'required|string|max:100',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->problemResponse(
                'https://tools.ietf.org/html/rfc7235#section-3.1',
                'Unauthorized',
                401,
                'Kredensial email atau password yang dimasukkan salah.'
            );
        }

        $token = $user->createToken($validated['device_name'])->plainTextToken;

        return $this->successResponse([
            'token_type'   => 'Bearer',
            'access_token' => $token,
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 'Otentikasi berhasil.');
    }

    /**
     * Mengambil data profil pustakawan yang sedang terautentikasi.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ], 'Profil pustakawan berhasil diambil.');
    }

    /**
     * Mencabut (revoke) token aktif yang sedang digunakan untuk sesi ini.
     */
    public function logout(Request $request): Response|JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return $this->noContentResponse();
    }
}
