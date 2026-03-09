<?php

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('patient_first_name');
            $table->string('patient_last_name');
            $table->date('patient_date_of_birth');
            $table->string('patient_phone')->nullable();
            $table->string('patient_email')->nullable();

            $table->text('reason');
            $table->string('priority')->default(ReferralPriority::Normal->value);
            $table->string('status')->default(ReferralStatus::Received->value);
            $table->string('source_system');
            $table->string('referring_provider')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('triaged_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('priority');
            $table->index(['patient_last_name', 'patient_first_name']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
