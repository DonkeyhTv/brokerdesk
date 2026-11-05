<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brokerage_id')->constrained();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('declared_by');
            $table->string('reference');
            $table->date('occurred_on');
            $table->text('description');
            $table->string('status');
            $table->integer('estimated_amount_cents')->nullable();
            $table->integer('settled_amount_cents')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->foreign(['contract_id', 'brokerage_id'])->references(['id', 'brokerage_id'])->on('contracts');
            $table->foreign(['declared_by', 'brokerage_id'])->references(['id', 'brokerage_id'])->on('users');
            $table->unique(['brokerage_id', 'reference']);
            $table->index(['brokerage_id', 'status']);
            $table->index('contract_id');
            $table->index('declared_by');
        });

        DB::statement("ALTER TABLE claims ADD CONSTRAINT claims_status_valid CHECK (status IN ('open', 'in_review', 'settled', 'rejected'))");
        DB::statement("ALTER TABLE claims ADD CONSTRAINT claims_closed_matches_status CHECK ((status IN ('settled', 'rejected')) = (closed_at IS NOT NULL))");
        DB::statement('ALTER TABLE claims ADD CONSTRAINT claims_amounts_positive CHECK (estimated_amount_cents >= 0 AND settled_amount_cents >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
