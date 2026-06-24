<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
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
     * Send an OTP message to the given WhatsApp number.
     *
     * @param  string  $phoneNumber  The recipient's WhatsApp number.
     * @param  string  $otpCode      The 6-digit OTP code.
     * @return array{success: bool, message: string}
     */
    public function sendOtp(string $phoneNumber, string $otpCode): array
    {
        // If API key is a placeholder or empty, simulate successful sending for testing
        if (empty($this->apiKey) || str_contains($this->apiKey, 'your-') || str_contains($this->apiKey, 'placeholder') || $this->apiKey === 'FONNTE_API_KEY') {
            Log::info("🏥 [MOCK OTP SEND] Nomor: {$phoneNumber} | Kode OTP: {$otpCode}");
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
            $response = Http::timeout(5)->withHeaders([
                'Authorization' => $this->apiKey,
            ])->post(self::API_URL, [
                'target'  => $phoneNumber,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp OTP sent successfully.', [
                    'phone' => $phoneNumber,
                ]);

                return [
                    'success' => true,
                    'message' => 'OTP berhasil dikirim.',
                ];
            }

            Log::warning('Fonnte API returned non-success response.', [
                'phone'    => $phoneNumber,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mengirim OTP. Silakan coba lagi.',
            ];
        } catch (ConnectionException $e) {
            Log::error('Failed to connect to Fonnte API.', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Layanan WhatsApp sedang tidak tersedia.',
            ];
        }
    }
}
