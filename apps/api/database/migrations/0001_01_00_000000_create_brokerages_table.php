<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brokerages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('enterprise_number', 10)->unique();
            $table->string('fsma_number')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('street');
            $table->char('postal_code', 4);
            $table->string('city');
            $table->timestamps();
        });

        DB::statement("ALTER TABLE brokerages ADD CONSTRAINT brokerages_enterprise_number_format CHECK (enterprise_number ~ '^[01][0-9]{9}$')");
        DB::statement("ALTER TABLE brokerages ADD CONSTRAINT brokerages_postal_code_format CHECK (postal_code ~ '^[1-9][0-9]{3}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('brokerages');
    }
};
