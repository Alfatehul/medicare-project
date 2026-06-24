<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Create a new AppointmentController instance.
     */
    public function __construct(
        private readonly MidtransService $midtransService,
    ) {}

    /**
     * Create a new appointment and initiate Midtrans payment.
     *
     * POST /api/v1/appointments
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'doctor_id'        => ['required', 'exists:doctors,id'],
            'appointment_date' => ['required', 'date', 'after:now'],
        ], [
            'doctor_id.required'        => 'ID dokter wajib diisi.',
            'doctor_id.exists'          => 'Dokter tidak ditemukan.',
            'appointment_date.required' => 'Tanggal appointment wajib diisi.',
            'appointment_date.date'     => 'Format tanggal tidak valid.',
            'appointment_date.after'    => 'Tanggal appointment harus di masa depan.',
        ]);

        $doctor = Doctor::findOrFail($validated['doctor_id']);
        $user = $request->user();

        // Create appointment with pending status
        $appointment = Appointment::create([
            'user_id'          => $user->id,
            'doctor_id'        => $doctor->id,
            'appointment_date' => $validated['appointment_date'],
            'status'           => 'pending',
        ]);

        // Generate unique order ID for Midtrans
        $orderId = 'MEDICARE-' . $appointment->id . '-' . time();

        // Create Midtrans Snap transaction
        $midtransResult = $this->midtransService->createTransaction(
            orderId: $orderId,
            grossAmount: $doctor->fee_idr,
            customerDetails: [
                'first_name' => $user->name,
                'phone'      => $user->whatsapp_number,
            ],
        );

        if (! $midtransResult['success']) {
            // Payment creation failed, but appointment is saved as pending
            return response()->json([
                'status'  => 'error',
                'message' => $midtransResult['message'],
                'data'    => [
                    'appointment' => $this->formatAppointment($appointment, $doctor),
                ],
            ], 502);
        }

        // Save payment token and redirect URL to appointment
        $appointment->update([
            'payment_token' => $midtransResult['token'],
            'redirect_url'  => $midtransResult['redirect_url'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Reservasi berhasil dibuat. Silakan lakukan pembayaran.',
            'data'    => [
                'appointment'  => $this->formatAppointment($appointment, $doctor),
                'payment'      => [
                    'token'        => $midtransResult['token'],
                    'redirect_url' => $midtransResult['redirect_url'],
                ],
            ],
        ], 201);
    }

    /**
     * Handle Midtrans payment webhook notification.
     *
     * POST /api/v1/payments/webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans webhook received.', ['payload' => $payload]);

        // Validate required fields
        $orderId           = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $statusCode        = (string) ($payload['status_code'] ?? '');
        $grossAmount       = (string) ($payload['gross_amount'] ?? '');
        $signatureKey      = $payload['signature_key'] ?? '';

        if (! $orderId || ! $transactionStatus) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data webhook tidak lengkap.',
            ], 400);
        }

        // Verify signature to ensure authenticity
        $isValid = $this->midtransService->verifySignature(
            orderId: $orderId,
            statusCode: $statusCode,
            grossAmount: $grossAmount,
            signatureKey: $signatureKey,
        );

        if (! $isValid) {
            Log::warning('Midtrans webhook signature verification failed.', [
                'order_id' => $orderId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Signature tidak valid.',
            ], 403);
        }

        // Extract appointment ID from order ID (format: MEDICARE-{id}-{timestamp})
        $parts = explode('-', $orderId);
        $appointmentId = $parts[1] ?? null;

        if (! $appointmentId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Format order_id tidak valid.',
            ], 400);
        }

        $appointment = Appointment::find($appointmentId);

        if (! $appointment) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Appointment tidak ditemukan.',
            ], 404);
        }

        // Update appointment status based on transaction status
        if (in_array($transactionStatus, ['settlement', 'capture'])) {
            $appointment->update(['status' => 'paid']);

            Log::info('Appointment marked as paid.', [
                'appointment_id' => $appointment->id,
                'order_id'       => $orderId,
            ]);
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $appointment->update(['status' => 'cancelled']);

            Log::info('Appointment marked as cancelled.', [
                'appointment_id' => $appointment->id,
                'order_id'       => $orderId,
                'reason'         => $transactionStatus,
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Webhook berhasil diproses.',
        ]);
    }

    /**
     * Format appointment data for JSON response.
     */
    private function formatAppointment(Appointment $appointment, Doctor $doctor): array
    {
        return [
            'id'               => $appointment->id,
            'doctor'           => [
                'id'             => $doctor->id,
                'name'           => $doctor->name,
                'specialization' => $doctor->specialization,
                'fee_idr'        => $doctor->fee_idr,
            ],
            'appointment_date' => $appointment->appointment_date->toIso8601String(),
            'status'           => $appointment->status,
        ];
    }
}
