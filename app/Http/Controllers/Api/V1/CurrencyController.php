<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Create a new CurrencyController instance.
     */
    public function __construct(
        private readonly CurrencyService $currencyService,
    ) {}

    /**
     * Convert an IDR amount to a target foreign currency.
     *
     * Useful for foreign tourists who want to know the doctor's
     * fee in their own currency before making an appointment.
     *
     * GET /api/v1/currency/convert?amount=200000&target=USD
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'target' => ['required', 'string', 'size:3', 'alpha'],
        ], [
            'amount.required' => 'Jumlah (amount) wajib diisi.',
            'amount.numeric'  => 'Jumlah harus berupa angka.',
            'amount.min'      => 'Jumlah minimal adalah 1.',
            'target.required' => 'Mata uang tujuan (target) wajib diisi.',
            'target.size'     => 'Kode mata uang harus 3 karakter (contoh: USD, EUR).',
            'target.alpha'    => 'Kode mata uang hanya boleh berisi huruf.',
        ]);

        $result = $this->currencyService->convert(
            amount: (float) $validated['amount'],
            target: $validated['target'],
        );

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
