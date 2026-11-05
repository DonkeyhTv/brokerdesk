<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS unaccent');

        // unaccent() is only STABLE, which generated columns and indexes do not accept.
        DB::statement("CREATE OR REPLACE FUNCTION immutable_unaccent(text) RETURNS text
            AS \$\$ SELECT public.unaccent('public.unaccent'::regdictionary, \$1) \$\$
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT");

        $this->replaceSearchName("immutable_unaccent(lower(coalesce(company_name, '') || ' ' || coalesce(first_name, '') || ' ' || coalesce(last_name, '')))");
    }

    public function down(): void
    {
        $this->replaceSearchName("lower(coalesce(company_name, '') || ' ' || coalesce(first_name, '') || ' ' || coalesce(last_name, ''))");

        DB::statement('DROP FUNCTION immutable_unaccent(text)');
    }

    private function replaceSearchName(string $expression): void
    {
        DB::statement('DROP INDEX clients_search_name_trgm');
        DB::statement('ALTER TABLE clients DROP COLUMN search_name');
        DB::statement("ALTER TABLE clients ADD COLUMN search_name text GENERATED ALWAYS AS ({$expression}) STORED");
        DB::statement('CREATE INDEX clients_search_name_trgm ON clients USING gin (search_name gin_trgm_ops)');
    }
};
