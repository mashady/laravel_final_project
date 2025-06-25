<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert 'role' from ENUM to VARCHAR and make it nullable
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR USING role::VARCHAR;");
        DB::statement("ALTER TABLE users ALTER COLUMN role DROP NOT NULL;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optionally revert to ENUM if you wish - be cautious as this is often irreversible without data loss
        // Example assumes you want to set back the enum:
        DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR;"); // Still varchar on down to avoid breaking change
        DB::statement("ALTER TABLE users ALTER COLUMN role SET NOT NULL;"); // Optional: make NOT NULL again if you want
    }
};
