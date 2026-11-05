<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_expiry_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('threshold_days');
            $table->date('expires_on');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['contract_id', 'threshold_days', 'expires_on']);
        });

        DB::statement('ALTER TABLE contract_expiry_alerts ADD CONSTRAINT contract_expiry_alerts_threshold_valid CHECK (threshold_days IN (30, 60, 90))');
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_expiry_alerts');
    }
};
