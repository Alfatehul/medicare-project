<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    /**
     * Fonnte API endpoint.
     */
    private const API_URL = 'https://api.fonnte.com/send';

    /**
     * Create a new WhatsappService instance.
     */
    public function __construct(
        private readonly string $apiKey,
    ) {}

    /**
     * Sanitize the phone number to international format (62xxx).
     *
     * Converts local format (08xxx) to 628xxx, strips leading '+',
     * and removes any non-digit characters.
     */
    public static function sanitizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-digit characters (spaces, dashes, parentheses)
        $phone = preg_replace('/\D/', '', $phoneNumber);

        // Convert leading '08' to '628'
        if (str_starts_with($phone, '08')) {
            $phone = '62' . substr($phone, 1);
        }

        // If it somehow starts with just '8' (without leading 0 or 62), prepend '62'
        if (str_starts_with($phone, '8') && !str_starts_with($phone, '62')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Send an OTP message to the given WhatsApp number.
     *
     * @param  string  $phoneNumber  The recipient's WhatsApp number.
     * @param  string  $otpCode      The 6-digit OTP code.
     * @return array{success: bool, message: string}
     */
    public function sendOtp(string $phoneNumber, string $otpCode): array
    {
        // Sanitize phone number to international format for Fonnte
        $sanitizedPhone = self::sanitizePhoneNumber($phoneNumber);

        // If API key is a placeholder or empty, simulate successful sending for testing
        if (empty($this->apiKey) || str_contains($this->apiKey, 'your-') || str_contains($this->apiKey, 'placeholder') || $this->apiKey === 'FONNTE_API_KEY') {
            Log::info("🏥 [MOCK OTP SEND] Nomor: {$sanitizedPhone} | Kode OTP: {$otpCode}");
            return [
                'success' => true,
                'message' => 'OTP berhasil dikirim (Simulasi: silakan periksa storage/logs/laravel.log untuk kodenya).',
            ];
        }

        $message = "🏥 *MediCare Clinic*\n\n"
            . "Kode OTP Anda: *{$otpCode}*\n"
            . "Berlaku selama 5 menit.\n\n"
            . "Jangan berikan kode ini kepada siapapun.";

        try {
            Log::info('Sending OTP via Fonnte API.', [
                'original_phone'  => $phoneNumber,
                'sanitized_phone' => $sanitizedPhone,
            ]);

            $response = Http::timeout(15)
                ->withOptions([
                    // Bypass local DNS resolution issue by specifying the resolved IP directly
                    'curl' => [
                        CURLOPT_RESOLVE => ['api.fonnte.com:443:103.52.212.50'],
                    ],
                ])
                ->withHeaders([
                    'Authorization' => $this->apiKey,
                ])->post(self::API_URL, [
                    'target'  => $sanitizedPhone,
                    'message' => $message,
                ]);

            $responseBody = $response->json();

            // Fonnte returns HTTP 200 even on logical errors.
            // Must check the 'status' field in the response body.
            if ($response->successful() && isset($responseBody['status']) && $responseBody['status'] === true) {
                Log::info('WhatsApp OTP sent successfully via Fonnte.', [
                    'phone'    => $sanitizedPhone,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP berhasil dikirim.',
                ];
            }

            Log::warning('Fonnte API returned non-success response.', [
                'phone'       => $sanitizedPhone,
                'http_status' => $response->status(),
                'response'    => $responseBody,
            ]);

            // Provide more specific error message from Fonnte if available
            $fonnteDetail = $responseBody['reason'] ?? $responseBody['message'] ?? 'Gagal mengirim OTP.';

            return [
                'success' => false,
                'message' => "Gagal mengirim OTP: {$fonnteDetail}",
            ];
        } catch (\Throwable $e) {
            Log::error('Exception while sending OTP via Fonnte.', [
                'phone'     => $sanitizedPhone,
                'exception' => get_class($e),
                'error'     => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Layanan WhatsApp sedang tidak tersedia. Silakan coba lagi nanti.',
            ];
        }
    }
}
