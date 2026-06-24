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
    public function __construct(
        private readonly NutritionService $nutritionService,
    ) {}

    /**
     * Search nutrition facts for a food item.
     *
     * GET /api/v1/nutrition/search?query=apple
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'query.required' => 'Query pencarian makanan wajib diisi.',
            'query.min'      => 'Query minimal harus berisi 2 karakter.',
            'query.max'      => 'Query maksimal berisi 100 karakter.',
        ]);

        $result = $this->nutritionService->searchFood($validated['query']);

        if (! $result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'],
            ], 502);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $result['message'],
            'data'    => $result['data'],
        ]);
    }
}
