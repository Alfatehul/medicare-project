<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NutritionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NutritionController extends Controller
{
    /**
     * Create a new NutritionController instance.
     */
    public function __construct() {}

    /**
     * Search nutrition facts for a food item.
     *
     * GET /api/v1/nutrition/search?query=apple
     */
    public function search(Request $request): JsonResponse
    {
        $makanan = $request->input('makanan') ?? $request->input('query');
        if (empty($makanan)) {
            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Nama makanan wajib diisi.',
            ], 400);
        }

        try {
            $apiKey = env('SPOONACULAR_API_KEY', '61ae501271034686968b6bab1768bda7');

            // Send request using Laravel's Http client as form
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->post('https://api.spoonacular.com/recipes/parseIngredients?apiKey=' . $apiKey, [
                    'ingredientList'   => $makanan,
                    'servings'         => 1,
                    'includeNutrition' => 'true',
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'Gagal menghubungi Spoonacular API.',
                ], 502);
            }

            $data = $response->json();

            // Spoonacular returns an array. Verify if the ingredient is successfully parsed.
            if (empty($data) || !isset($data[0]) || (isset($data[0]['id']) && $data[0]['id'] === 0) || empty($data[0]['name'])) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'Makanan tidak ditemukan.',
                ], 404);
            }

            $nutrients = $data[0]['nutrition']['nutrients'] ?? [];
            if (empty($nutrients)) {
                return response()->json([
                    'success' => false,
                    'status'  => 'error',
                    'message' => 'Informasi nutrisi tidak ditemukan untuk makanan ini.',
                ], 404);
            }

            $calories = 0;
            $protein = 0;
            $fat = 0;
            $carbs = 0;

            foreach ($nutrients as $nutrient) {
                if (!isset($nutrient['name']) || !isset($nutrient['amount'])) {
                    continue;
                }
                switch ($nutrient['name']) {
                    case 'Calories':
                        $calories = $nutrient['amount'];
                        break;
                    case 'Protein':
                        $protein = $nutrient['amount'];
                        break;
                    case 'Fat':
                        $fat = $nutrient['amount'];
                        break;
                    case 'Carbohydrates':
                        $carbs = $nutrient['amount'];
                        break;
                }
            }

            // Return response structure as requested by the user,
            // while keeping it compatible with our existing Next.js frontend requirements.
            return response()->json([
                'success' => true,
                'status'  => 'success',
                'message' => 'Analisis gizi berhasil.',
                'data'    => [
                    'calories' => $calories,
                    'protein'  => $protein,
                    'fat'      => $fat,
                    'carbs'    => $carbs,
                    'query'    => $makanan,
                    'items'    => [
                        [
                            'id' => (string) ($data[0]['id'] ?? '1'),
                            'label' => $data[0]['name'] ?? $makanan,
                            'category' => $data[0]['aisle'] ?? 'Generic',
                            'image' => isset($data[0]['image']) ? 'https://spoonacular.com/cdn/ingredients_100x100/' . $data[0]['image'] : null,
                            'nutrients' => [
                                'calories_kcal' => $calories,
                                'protein_g'     => $protein,
                                'fat_g'         => $fat,
                                'carbs_g'       => $carbs,
                                'fiber_g'       => 0,
                            ],
                        ],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Nutrition parsing error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
            ], 500);
        }
    }
}
