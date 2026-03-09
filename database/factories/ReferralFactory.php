<?php

namespace Database\Factories;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referral>
 */
class ReferralFactory extends Factory
{
    protected $model = Referral::class;

    public function definition(): array
    {
        return [
            'patient_first_name' => fake()->firstName(),
            'patient_last_name' => fake()->lastName(),
            'patient_date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'patient_phone' => fake()->phoneNumber(),
            'patient_email' => fake()->safeEmail(),
            'reason' => fake()->sentence(10),
            'priority' => fake()->randomElement(ReferralPriority::cases()),
            'source_system' => fake()->randomElement(['EMR-System', 'ClinicPortal', 'PartnerAPI', 'PatientApp']),
            'referring_provider' => fake()->optional(0.8)->name(),
            'notes' => fake()->optional(0.5)->paragraph(),
            'status' => ReferralStatus::Received,
        ];
    }

    public function triaging(): static
    {
        return $this->state(['status' => ReferralStatus::Triaging]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => ReferralStatus::Accepted,
            'triaged_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => ReferralStatus::Rejected,
            'triaged_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => ReferralStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function priority(ReferralPriority $priority): static
    {
        return $this->state(['priority' => $priority]);
    }
}
