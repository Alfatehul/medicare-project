<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Abstract API Exchange Rate endpoint.
     */
    private const API_URL = 'https://api.abstractapi.com/v1/exchangerate/live';

    /**
     * Base currency for conversion.
     */
    private const BASE_CURRENCY = 'IDR';

    /**
     * Create a new CurrencyService instance.
     */
    public function __construct(
        private readonly string $apiKey,
    ) {}

    /**
     * Convert an amount from IDR to a target currency.
     *
     * @param  float   $amount    The amount in IDR.
     * @param  string  $target    Target currency code (e.g. USD, EUR).
     * @return array{success: bool, data: array|null, message: string}
     */
    public function convert(float $amount, string $target): array
    {
        $target = strtoupper($target);

        try {
            $response = Http::timeout(5)->get(self::API_URL, [
                'api_key'       => $this->apiKey,
                'base'          => self::BASE_CURRENCY,
                'target'        => $target,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $rate = $data['exchange_rates'][$target]['value'] ?? null;

                if ($rate === null) {
                    return [
                        'success' => false,
                        'data'    => null,
                        'message' => "Kurs untuk mata uang {$target} tidak ditemukan.",
                    ];
                }

                $convertedAmount = round($amount * $rate, 2);

                return [
                    'success' => true,
                    'data'    => [
                        'base_currency'      => self::BASE_CURRENCY,
                        'target_currency'    => $target,
                        'exchange_rate'      => $rate,
                        'original_amount'    => $amount,
                        'converted_amount'   => $convertedAmount,
                    ],
                    'message' => 'Konversi mata uang berhasil.',
                ];
            }

            Log::warning('Abstract API returned non-success response.', [
                'target'   => $target,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Gagal mengambil data kurs mata uang.',
            ];
        } catch (ConnectionException $e) {
            Log::error('Failed to connect to Abstract API.', [
                'target' => $target,
                'error'  => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Layanan konversi mata uang sedang tidak tersedia.',
            ];
        }
    }
}
