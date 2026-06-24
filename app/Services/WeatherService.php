<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * OpenWeatherMap API endpoint.
     */
    private const API_URL = 'https://api.openweathermap.org/data/2.5/weather';

    /**
     * Default city for weather data.
     */
    private const DEFAULT_CITY = 'Banda_Aceh';

    /**
     * Create a new WeatherService instance.
     */
    public function __construct(
        private readonly string $apiKey,
    ) {}

    /**
     * Get current weather data for the clinic's city.
     *
     * @param  string  $city  City name (defaults to Banda_Aceh).
     * @return array{success: bool, data: array|null, message: string}
     */
    public function getCurrentWeather(string $city = self::DEFAULT_CITY): array
    {
        try {
            $response = Http::timeout(5)->get(self::API_URL, [
                'q'     => str_replace('_', ' ', $city),
                'appid' => $this->apiKey,
                'units' => 'metric',
                'lang'  => 'id',
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'data'    => [
                        'city'        => $data['name'] ?? $city,
                        'temperature' => $data['main']['temp'] ?? null,
                        'feels_like'  => $data['main']['feels_like'] ?? null,
                        'humidity'    => $data['main']['humidity'] ?? null,
                        'description' => $data['weather'][0]['description'] ?? null,
                        'icon'        => isset($data['weather'][0]['icon'])
                            ? "https://openweathermap.org/img/wn/{$data['weather'][0]['icon']}@2x.png"
                            : null,
                    ],
                    'message' => 'Data cuaca berhasil diambil.',
                ];
            }

            Log::warning('OpenWeather API returned non-success response.', [
                'city'     => $city,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Gagal mengambil data cuaca.',
            ];
        } catch (ConnectionException $e) {
            Log::error('Failed to connect to OpenWeather API.', [
                'city'  => $city,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Layanan cuaca sedang tidak tersedia.',
            ];
        }
    }
}
