<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brokerage_id')->constrained();
            $table->unsignedBigInteger('broker_id');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('type');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('company_name')->nullable();
            $table->char('enterprise_number', 10)->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('street');
            $table->char('postal_code', 4);
            $table->string('city');
            $table->text('search_name')->storedAs(
                "lower(coalesce(company_name, '') || ' ' || coalesce(first_name, '') || ' ' || coalesce(last_name, ''))"
            );
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['broker_id', 'brokerage_id'])->references(['id', 'brokerage_id'])->on('users');
            $table->foreign(['user_id', 'brokerage_id'])->references(['id', 'brokerage_id'])->on('users');
            $table->unique(['id', 'brokerage_id']);
            $table->index(['brokerage_id', 'broker_id']);
        });

        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_identity_matches_type CHECK (
            (type = 'individual' AND first_name IS NOT NULL AND last_name IS NOT NULL AND company_name IS NULL AND enterprise_number IS NULL)
            OR (type = 'company' AND company_name IS NOT NULL AND enterprise_number IS NOT NULL)
        )");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_enterprise_number_format CHECK (enterprise_number ~ '^[01][0-9]{9}$')");
        DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_postal_code_format CHECK (postal_code ~ '^[1-9][0-9]{3}$')");
        DB::statement('CREATE UNIQUE INDEX clients_brokerage_enterprise_number_unique ON clients (brokerage_id, enterprise_number) WHERE enterprise_number IS NOT NULL AND deleted_at IS NULL');
        DB::statement('CREATE INDEX clients_search_name_trgm ON clients USING gin (search_name gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
