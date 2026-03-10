<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Events\ReferralCreated;
use App\Listeners\TriageReferralListener;
use App\Models\Referral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReferralApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-service-token';

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.service_token' => $this->token]);
    }

    private function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ], $extra);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'patient_first_name' => 'John',
            'patient_last_name' => 'Doe',
            'patient_date_of_birth' => '1990-05-15',
            'patient_phone' => '+1234567890',
            'patient_email' => 'john@example.com',
            'reason' => 'Chronic back pain requiring specialist evaluation',
            'priority' => 'high',
            'source_system' => 'EMR-System',
            'referring_provider' => 'Dr. Jane Smith',
            'notes' => 'Patient has history of lumbar issues',
        ], $overrides);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->postJson('/api/v1/referrals', $this->validPayload());

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid or missing API service token.']);
    }

    public function test_invalid_token_returns_401(): void
    {
        $response = $this->postJson(
            '/api/v1/referrals',
            $this->validPayload(),
            ['Authorization' => 'Bearer wrong-token', 'Accept' => 'application/json'],
        );

        $response->assertStatus(401);
    }

    public function test_create_referral_successfully(): void
    {
        Event::fake([ReferralCreated::class]);

        $response = $this->postJson(
            '/api/v1/referrals',
            $this->validPayload(),
            $this->authHeaders(),
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'patient' => ['first_name', 'last_name', 'date_of_birth', 'phone', 'email'],
                    'reason',
                    'priority',
                    'status',
                    'source_system',
                    'referring_provider',
                    'notes',
                    'triaged_at',
                    'cancelled_at',
                    'cancellation_reason',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.patient.first_name', 'John')
            ->assertJsonPath('data.patient.last_name', 'Doe')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'received');

        $this->assertDatabaseHas('referrals', [
            'patient_first_name' => 'John',
            'patient_last_name' => 'Doe',
            'status' => ReferralStatus::Received->value,
        ]);

        Event::assertDispatched(ReferralCreated::class);
    }

    public function test_create_referral_with_minimal_fields(): void
    {
        Event::fake([ReferralCreated::class]);

        $payload = [
            'patient_first_name' => 'Jane',
            'patient_last_name' => 'Smith',
            'patient_date_of_birth' => '1985-03-20',
            'reason' => 'Annual checkup referral',
            'source_system' => 'ClinicPortal',
        ];

        $response = $this->postJson('/api/v1/referrals', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('data.priority', 'normal')
            ->assertJsonPath('data.patient.phone', null)
            ->assertJsonPath('data.patient.email', null);
    }

    public function test_create_referral_dispatches_event(): void
    {
        Event::fake([ReferralCreated::class]);

        $this->postJson('/api/v1/referrals', $this->validPayload(), $this->authHeaders());

        Event::assertDispatched(ReferralCreated::class, function ($event) {
            return $event->referral->patient_first_name === 'John';
        });
    }

    public function test_create_referral_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/v1/referrals', [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'patient_first_name',
                'patient_last_name',
                'patient_date_of_birth',
                'reason',
                'source_system',
            ]);
    }

    public function test_create_referral_fails_with_invalid_priority(): void
    {
        $payload = $this->validPayload(['priority' => 'critical']);

        $response = $this->postJson('/api/v1/referrals', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_create_referral_fails_with_future_date_of_birth(): void
    {
        $payload = $this->validPayload(['patient_date_of_birth' => '2099-01-01']);

        $response = $this->postJson('/api/v1/referrals', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_date_of_birth']);
    }

    public function test_create_referral_fails_with_invalid_email(): void
    {
        $payload = $this->validPayload(['patient_email' => 'not-an-email']);

        $response = $this->postJson('/api/v1/referrals', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['patient_email']);
    }

    public function test_show_referral(): void
    {
        $referral = Referral::factory()->create();

        $response = $this->getJson("/api/v1/referrals/{$referral->id}", $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('data.id', $referral->id)
            ->assertJsonPath('data.patient.first_name', $referral->patient_first_name);
    }

    public function test_show_nonexistent_referral_returns_404(): void
    {
        $response = $this->getJson('/api/v1/referrals/non-existent-uuid', $this->authHeaders());

        $response->assertStatus(404);
    }

    public function test_list_referrals_with_pagination(): void
    {
        Referral::factory()->count(20)->create();

        $response = $this->getJson('/api/v1/referrals?per_page=5', $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_list_referrals_filter_by_status(): void
    {
        Referral::factory()->count(3)->create();
        Referral::factory()->count(2)->cancelled()->create();

        $response = $this->getJson('/api/v1/referrals?status=cancelled', $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        collect($response->json('data'))->each(function ($item) {
            $this->assertEquals('cancelled', $item['status']);
        });
    }

    public function test_list_referrals_filter_by_priority(): void
    {
        Referral::factory()->priority(ReferralPriority::Urgent)->count(2)->create();
        Referral::factory()->priority(ReferralPriority::Low)->count(3)->create();

        $response = $this->getJson('/api/v1/referrals?priority=urgent', $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_list_referrals_with_search(): void
    {
        Referral::factory()->create(['patient_last_name' => 'Uniqueson']);
        Referral::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/referrals?search=Uniqueson', $this->authHeaders());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.patient.last_name', 'Uniqueson');
    }

    public function test_cancel_received_referral(): void
    {
        $referral = Referral::factory()->create();

        $response = $this->postJson(
            "/api/v1/referrals/{$referral->id}/cancel",
            ['reason' => 'Patient requested cancellation'],
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellation_reason', 'Patient requested cancellation');

        $this->assertNotNull($response->json('data.cancelled_at'));
    }

    public function test_cancel_triaging_referral(): void
    {
        $referral = Referral::factory()->triaging()->create();

        $response = $this->postJson(
            "/api/v1/referrals/{$referral->id}/cancel",
            [],
            $this->authHeaders(),
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cannot_cancel_accepted_referral(): void
    {
        $referral = Referral::factory()->accepted()->create();

        $response = $this->postJson(
            "/api/v1/referrals/{$referral->id}/cancel",
            [],
            $this->authHeaders(),
        );

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_rejected_referral(): void
    {
        $referral = Referral::factory()->rejected()->create();

        $response = $this->postJson(
            "/api/v1/referrals/{$referral->id}/cancel",
            [],
            $this->authHeaders(),
        );

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_already_cancelled_referral(): void
    {
        $referral = Referral::factory()->cancelled()->create();

        $response = $this->postJson(
            "/api/v1/referrals/{$referral->id}/cancel",
            [],
            $this->authHeaders(),
        );

        $response->assertStatus(422);
    }

    public function test_triage_listener_transitions_referral_status(): void
    {
        $referral = Referral::factory()->create([
            'status' => ReferralStatus::Received,
        ]);

        $listener = new TriageReferralListener();
        $listener->handle(new ReferralCreated($referral));

        $referral->refresh();

        $this->assertContains($referral->status, [
            ReferralStatus::Accepted,
            ReferralStatus::Rejected,
        ]);
        $this->assertNotNull($referral->triaged_at);
    }

    public function test_triage_listener_skips_non_received_referral(): void
    {
        $referral = Referral::factory()->cancelled()->create();

        $listener = new TriageReferralListener();
        $listener->handle(new ReferralCreated($referral));

        $referral->refresh();

        $this->assertEquals(ReferralStatus::Cancelled, $referral->status);
    }

    public function test_idempotency_key_prevents_duplicate_creation(): void
    {
        Event::fake([ReferralCreated::class]);

        $headers = $this->authHeaders(['Idempotency-Key' => 'unique-key-123']);

        $first = $this->postJson('/api/v1/referrals', $this->validPayload(), $headers);
        $first->assertStatus(201);

        $second = $this->postJson(
            '/api/v1/referrals',
            $this->validPayload(['patient_first_name' => 'Different']),
            $headers,
        );

        $second->assertStatus(201)
            ->assertHeader('X-Idempotency-Replayed', 'true')
            ->assertJsonPath('data.id', $first->json('data.id'));

        Event::assertDispatchedTimes(ReferralCreated::class, 1);
    }
}
