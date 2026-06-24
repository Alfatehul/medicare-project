<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NutritionService
{
    /**
     * Edamam Food Database Parser API endpoint.
     */
    private const API_URL = 'https://api.edamam.com/api/food-database/v2/parser';

    /**
     * Create a new NutritionService instance.
     */
    public function __construct(
        private readonly string $appId,
        private readonly string $appKey,
    ) {}

    /**
     * Search for food items and retrieve their nutritional info.
     *
     * @param  string  $query  The food item search query (e.g. "apple").
     * @return array{success: bool, data: array|null, message: string}
     */
    public function searchFood(string $query): array
    {
        try {
            $response = Http::timeout(5)->get(self::API_URL, [
                'app_id' => $this->appId,
                'app_key' => $this->appKey,
                'ingr'   => $query,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $hints = $data['hints'] ?? [];

                if (empty($hints)) {
                    return [
                        'success' => false,
                        'data'    => null,
                        'message' => "Makanan '{$query}' tidak ditemukan di database gizi.",
                    ];
                }

                $foodItems = [];
                foreach (array_slice($hints, 0, 5) as $hint) {
                    $food = $hint['food'] ?? null;
                    if (! $food) {
                        continue;
                    }

                    $nutrients = $food['nutrients'] ?? [];

                    $foodItems[] = [
                        'id'          => $food['foodId'] ?? null,
                        'label'       => $food['label'] ?? null,
                        'category'    => $food['category'] ?? null,
                        'image'       => $food['image'] ?? null,
                        'nutrients'   => [
                            'calories_kcal' => $nutrients['ENERC_KCAL'] ?? 0,
                            'protein_g'     => $nutrients['PROCNT'] ?? 0,
                            'fat_g'         => $nutrients['FAT'] ?? 0,
                            'carbs_g'       => $nutrients['CHOCDF'] ?? 0,
                            'fiber_g'       => $nutrients['FIBTG'] ?? 0,
                        ],
                    ];
                }

                return [
                    'success' => true,
                    'data'    => [
                        'query' => $query,
                        'items' => $foodItems,
                    ],
                    'message' => 'Data gizi berhasil diambil.',
                ];
            }

            Log::warning('Edamam API returned non-success response.', [
                'query'    => $query,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Gagal mengambil data gizi dari layanan pihak ketiga.',
            ];
        } catch (ConnectionException $e) {
            Log::error('Failed to connect to Edamam API.', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'data'    => null,
                'message' => 'Layanan analisis gizi sedang tidak tersedia.',
            ];
        }
    }
}
