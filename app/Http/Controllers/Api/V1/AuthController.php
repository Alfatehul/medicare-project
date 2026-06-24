<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct(
        private readonly WhatsappService $whatsappService,
    ) {}

    /**
     * Request OTP for the given WhatsApp number.
     *
     * Generates a 6-digit OTP, stores it in cache for 5 minutes,
     * and sends it via WhatsApp using the Fonnte API.
     *
     * POST /api/v1/auth/request-otp
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'whatsapp_number' => ['required', 'string', 'regex:/^(08|\+62|62)[0-9]{8,13}$/'],
        ], [
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex'    => 'Format nomor WhatsApp tidak valid.',
        ]);

        $phoneNumber = $validated['whatsapp_number'];
        $cacheKey = "otp:{$phoneNumber}";

        // Prevent OTP flooding: check if an OTP was recently sent
        if (Cache::has($cacheKey)) {
            $ttl = Cache::get("{$cacheKey}:sent_at");

            if ($ttl && now()->diffInSeconds($ttl) < 60) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'OTP sudah dikirim. Tunggu 60 detik sebelum meminta ulang.',
                ], 429);
            }
        }

        // Generate 6-digit OTP
        $otpCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 5 minutes
        Cache::put($cacheKey, $otpCode, now()->addMinutes(5));
        Cache::put("{$cacheKey}:sent_at", now(), now()->addMinutes(5));

        // Send OTP via WhatsApp
        $result = $this->whatsappService->sendOtp($phoneNumber, $otpCode);

        if (! $result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'],
            ], 502);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'OTP berhasil dikirim ke nomor WhatsApp Anda.',
        ]);
    }

    /**
     * Verify OTP and authenticate the user.
     *
     * Validates the OTP against the cached value, creates or retrieves
     * the user, and returns a Sanctum bearer token.
     *
     * POST /api/v1/auth/verify-otp
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'whatsapp_number' => ['required', 'string', 'regex:/^(08|\+62|62)[0-9]{8,13}$/'],
            'otp_code'        => ['required', 'string', 'size:6'],
        ], [
            'whatsapp_number.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp_number.regex'    => 'Format nomor WhatsApp tidak valid.',
            'otp_code.required'        => 'Kode OTP wajib diisi.',
            'otp_code.size'            => 'Kode OTP harus 6 digit.',
        ]);

        $phoneNumber = $validated['whatsapp_number'];
        $otpCode = $validated['otp_code'];
        $cacheKey = "otp:{$phoneNumber}";

        // Retrieve cached OTP
        $cachedOtp = Cache::get($cacheKey);

        if (! $cachedOtp || $cachedOtp !== $otpCode) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.',
            ], 422);
        }

        // OTP is valid — clear from cache
        Cache::forget($cacheKey);
        Cache::forget("{$cacheKey}:sent_at");

        // Find or create user by WhatsApp number
        $user = User::firstOrCreate(
            ['whatsapp_number' => $phoneNumber],
            ['name' => 'User ' . Str::substr($phoneNumber, -4)],
        );

        // Revoke existing tokens for this device context
        $user->tokens()->where('name', 'medicare-api')->delete();

        // Create new Sanctum token
        $token = $user->createToken('medicare-api')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Verifikasi berhasil.',
            'data'   => [
                'user'  => [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'whatsapp_number' => $user->whatsapp_number,
                ],
                'token_type'   => 'Bearer',
                'access_token' => $token,
            ],
        ]);
    }
}
