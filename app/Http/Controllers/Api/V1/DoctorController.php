<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class DoctorController extends Controller
{
    /**
     * Create a new DoctorController instance.
     */
    public function __construct(
        private readonly WeatherService $weatherService,
    ) {}

    /**
     * Get all doctors with current weather metadata.
     *
     * Returns a list of all registered doctors along with the
     * current weather information for the clinic's location.
     *
     * GET /api/v1/doctors
     */
    public function index(): JsonResponse
    {
        $doctors = Doctor::all(['id', 'name', 'specialization', 'fee_idr']);

        // Fetch current weather for clinic location
        $weather = $this->weatherService->getCurrentWeather();

        return response()->json([
            'status' => 'success',
            'meta'   => [
                'total'   => $doctors->count(),
                'weather' => $weather['success']
                    ? $weather['data']
                    : ['message' => $weather['message']],
            ],
            'data' => $doctors,
        ]);
    }
}
