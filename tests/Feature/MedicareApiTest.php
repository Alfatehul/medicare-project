<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\MidtransService;
use App\Services\WeatherService;
use App\Services\WhatsappService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MedicareApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test sending OTP via WhatsApp.
     */
    public function test_can_request_otp(): void
    {
        // Mock WhatsappService
        $whatsappMock = Mockery::mock(WhatsappService::class);
        $whatsappMock->shouldReceive('sendOtp')
            ->once()
            ->with('081234567890', Mockery::type('string'))
            ->andReturn(['success' => true, 'message' => 'OTP berhasil dikirim.']);

        $this->app->instance(WhatsappService::class, $whatsappMock);

        $response = $this->postJson('/api/v1/auth/request-otp', [
            'whatsapp_number' => '081234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'OTP berhasil dikirim ke nomor WhatsApp Anda.',
            ]);

        // Check if OTP is stored in Cache
        $this->assertTrue(Cache::has('otp:081234567890'));
    }

    /**
     * Test validating incorrect OTP.
     */
    public function test_cannot_verify_invalid_otp(): void
    {
        Cache::put('otp:081234567890', '123456', 5);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'whatsapp_number' => '081234567890',
            'otp_code'        => '000000', // Invalid OTP
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status'  => 'error',
                'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.',
            ]);
    }

    /**
     * Test verifying correct OTP creates user and returns token.
     */
    public function test_can_verify_valid_otp_and_authenticate(): void
    {
        Cache::put('otp:081234567890', '123456', 5);

        $response = $this->postJson('/api/v1/auth/verify-otp', [
            'whatsapp_number' => '081234567890',
            'otp_code'        => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'whatsapp_number',
                    ],
                    'token_type',
                    'access_token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'whatsapp_number' => '081234567890',
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has('otp:081234567890'));
    }

    /**
     * Test fetching doctors list with weather metadata.
     */
    public function test_can_list_doctors_with_weather_metadata(): void
    {
        // Seed dummy doctors
        Doctor::create([
            'name'           => 'Dr. Andi Pratama',
            'specialization' => 'Spesialis Anak',
            'fee_idr'        => 150000,
        ]);

        // Mock WeatherService
        $weatherMock = Mockery::mock(WeatherService::class);
        $weatherMock->shouldReceive('getCurrentWeather')
            ->once()
            ->andReturn([
                'success' => true,
                'data'    => [
                    'city'        => 'Banda Aceh',
                    'temperature' => 29.5,
                    'feels_like'  => 32.1,
                    'humidity'    => 75,
                    'description' => 'cerah berawan',
                    'icon'        => '02d',
                ],
                'message' => 'Data cuaca berhasil diambil.',
            ]);

        $this->app->instance(WeatherService::class, $weatherMock);

        $response = $this->getJson('/api/v1/doctors');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'meta'   => [
                    'total'   => 1,
                    'weather' => [
                        'city'        => 'Banda Aceh',
                        'temperature' => 29.5,
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test currency conversion.
     */
    public function test_can_convert_currency(): void
    {
        // Mock CurrencyService
        $currencyMock = Mockery::mock(CurrencyService::class);
        $currencyMock->shouldReceive('convert')
            ->once()
            ->with(200000, 'USD')
            ->andReturn([
                'success' => true,
                'data'    => [
                    'base_currency'      => 'IDR',
                    'target_currency'    => 'USD',
                    'exchange_rate'      => 0.000061,
                    'original_amount'    => 200000,
                    'converted_amount'   => 12.2,
                ],
                'message' => 'Konversi mata uang berhasil.',
            ]);

        $this->app->instance(CurrencyService::class, $currencyMock);

        $response = $this->getJson('/api/v1/currency/convert?amount=200000&target=USD');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'target_currency'  => 'USD',
                    'converted_amount' => 12.2,
                ],
            ]);
    }

    /**
     * Test creating an appointment and generating Midtrans payment.
     */
    public function test_can_create_appointment_with_midtrans_payment(): void
    {
        $user = User::create([
            'name'            => 'John Doe',
            'whatsapp_number' => '081234567890',
        ]);

        $doctor = Doctor::create([
            'name'           => 'Dr. Budi Santoso',
            'specialization' => 'Spesialis Gigi',
            'fee_idr'        => 200000,
        ]);

        // Mock MidtransService
        $midtransMock = Mockery::mock(MidtransService::class);
        $midtransMock->shouldReceive('createTransaction')
            ->once()
            ->with(
                Mockery::type('string'), // order_id
                200000,                  // fee_idr
                [
                    'first_name' => 'John Doe',
                    'phone'      => '081234567890',
                ]
            )
            ->andReturn([
                'success'      => true,
                'token'        => 'snap-token-123',
                'redirect_url' => 'https://redirect.url/snap-123',
                'message'      => 'Transaksi pembayaran berhasil dibuat.',
            ]);

        $this->app->instance(MidtransService::class, $midtransMock);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/appointments', [
                'doctor_id'        => $doctor->id,
                'appointment_date' => now()->addDays(2)->toIso8601String(),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'appointment' => [
                        'status' => 'pending',
                    ],
                    'payment'     => [
                        'token'        => 'snap-token-123',
                        'redirect_url' => 'https://redirect.url/snap-123',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('appointments', [
            'user_id'       => $user->id,
            'doctor_id'     => $doctor->id,
            'status'        => 'pending',
            'payment_token' => 'snap-token-123',
            'redirect_url'  => 'https://redirect.url/snap-123',
        ]);
    }

    /**
     * Test Midtrans Webhook updates appointment status to paid on settlement.
     */
    public function test_midtrans_webhook_updates_status_on_settlement(): void
    {
        $user = User::create([
            'name'            => 'John Doe',
            'whatsapp_number' => '081234567890',
        ]);

        $doctor = Doctor::create([
            'name'           => 'Dr. Budi Santoso',
            'specialization' => 'Spesialis Gigi',
            'fee_idr'        => 200000,
        ]);

        $appointment = $user->appointments()->create([
            'doctor_id'        => $doctor->id,
            'appointment_date' => now()->addDays(2),
            'status'           => 'pending',
        ]);

        // Mock MidtransService
        $midtransMock = Mockery::mock(MidtransService::class);
        $midtransMock->shouldReceive('verifySignature')
            ->once()
            ->andReturn(true);

        $this->app->instance(MidtransService::class, $midtransMock);

        $response = $this->postJson('/api/v1/payments/webhook', [
            'order_id'           => 'MEDICARE-' . $appointment->id . '-123456',
            'transaction_status' => 'settlement',
            'status_code'        => '200',
            'gross_amount'       => '200000.00',
            'signature_key'      => 'fake-signature-key',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Webhook berhasil diproses.',
            ]);

        $this->assertDatabaseHas('appointments', [
            'id'     => $appointment->id,
            'status' => 'paid',
        ]);
    }

    /**
     * Test searching nutrition facts via Edamam API (authenticated).
     */
    public function test_can_search_nutrition_facts(): void
    {
        $user = User::create([
            'name'            => 'John Doe',
            'whatsapp_number' => '081234567890',
        ]);

        // Mock NutritionService
        $nutritionMock = Mockery::mock(\App\Services\NutritionService::class);
        $nutritionMock->shouldReceive('searchFood')
            ->once()
            ->with('apple')
            ->andReturn([
                'success' => true,
                'data'    => [
                    'query' => 'apple',
                    'items' => [
                        [
                            'id' => 'food_apple',
                            'label' => 'Apple',
                            'category' => 'Packaged foods',
                            'image' => 'https://edamam.com/apple.png',
                            'nutrients' => [
                                'calories_kcal' => 52,
                                'protein_g' => 0.26,
                                'fat_g' => 0.17,
                                'carbs_g' => 13.81,
                                'fiber_g' => 2.4,
                            ],
                        ],
                    ],
                ],
                'message' => 'Data gizi berhasil diambil.',
            ]);

        $this->app->instance(\App\Services\NutritionService::class, $nutritionMock);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/nutrition/search?query=apple');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'query' => 'apple',
                ],
            ])
            ->assertJsonCount(1, 'data.items');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
