<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brokerage_id')->constrained();
            $table->unsignedBigInteger('client_id');
            $table->foreignId('insurer_id')->index()->constrained();
            $table->string('policy_number');
            $table->string('type');
            $table->string('status');
            $table->date('starts_on');
            $table->date('expires_on');
            $table->integer('annual_premium_cents');
            $table->string('payment_frequency');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['client_id', 'brokerage_id'])->references(['id', 'brokerage_id'])->on('clients');
            $table->unique(['id', 'brokerage_id']);
            $table->unique(['brokerage_id', 'policy_number']);
            $table->index(['brokerage_id', 'status', 'expires_on']);
            $table->index('client_id');
        });

        DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_type_valid CHECK (type IN ('motor', 'home', 'liability', 'life', 'health', 'legal', 'business'))");
        DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_valid CHECK (status IN ('active', 'suspended', 'cancelled', 'expired'))");
        DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_payment_frequency_valid CHECK (payment_frequency IN ('annual', 'semiannual', 'quarterly', 'monthly'))");
        DB::statement('ALTER TABLE contracts ADD CONSTRAINT contracts_dates_ordered CHECK (expires_on > starts_on)');
        DB::statement('ALTER TABLE contracts ADD CONSTRAINT contracts_premium_positive CHECK (annual_premium_cents >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
