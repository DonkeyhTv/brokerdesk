<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->index()->constrained();
            $table->foreignId('uploaded_by')->index()->constrained('users');
            $table->string('original_name');
            $table->string('path')->unique();
            $table->integer('size_bytes');
            $table->string('mime_type');
            $table->boolean('visible_to_client')->default(false);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE documents ADD CONSTRAINT documents_size_positive CHECK (size_bytes >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
