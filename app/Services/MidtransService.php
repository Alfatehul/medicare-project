<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    /**
     * Midtrans Snap API endpoint (Sandbox).
     */
    private const SNAP_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    /**
     * Create a new MidtransService instance.
     */
    public function __construct(
        private readonly string $serverKey,
    ) {}

    /**
     * Create a Snap payment transaction.
     *
     * @param  string  $orderId       Unique order identifier.
     * @param  int     $grossAmount   Total amount in IDR.
     * @param  array   $customerDetails  Customer information.
     * @return array{success: bool, token: string|null, redirect_url: string|null, message: string}
     */
    public function createTransaction(
        string $orderId,
        int $grossAmount,
        array $customerDetails = [],
    ): array {
        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $grossAmount,
            ],
        ];

        if (! empty($customerDetails)) {
            $payload['customer_details'] = $customerDetails;
        }

        try {
            $response = Http::timeout(5)->withBasicAuth($this->serverKey, '')
                ->post(self::SNAP_URL, $payload);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Midtrans Snap transaction created.', [
                    'order_id' => $orderId,
                    'token'    => $data['token'] ?? null,
                ]);

                return [
                    'success'      => true,
                    'token'        => $data['token'] ?? null,
                    'redirect_url' => $data['redirect_url'] ?? null,
                    'message'      => 'Transaksi pembayaran berhasil dibuat.',
                ];
            }

            Log::warning('Midtrans API returned non-success response.', [
                'order_id' => $orderId,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success'      => false,
                'token'        => null,
                'redirect_url' => null,
                'message'      => 'Gagal membuat transaksi pembayaran.',
            ];
        } catch (ConnectionException $e) {
            Log::error('Failed to connect to Midtrans API.', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success'      => false,
                'token'        => null,
                'redirect_url' => null,
                'message'      => 'Layanan pembayaran sedang tidak tersedia.',
            ];
        }
    }

    /**
     * Verify the signature of a Midtrans webhook notification.
     *
     * @param  string  $orderId           The order ID from the notification.
     * @param  string  $statusCode        The status code from the notification.
     * @param  string  $grossAmount       The gross amount from the notification.
     * @param  string  $signatureKey      The signature key from the notification.
     * @return bool
     */
    public function verifySignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $signatureKey,
    ): bool {
        $expectedSignature = hash(
            'sha512',
            $orderId . $statusCode . $grossAmount . $this->serverKey,
        );

        return hash_equals($expectedSignature, $signatureKey);
    }
}
