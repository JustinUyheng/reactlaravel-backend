<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change the column to VARCHAR(50)
        DB::statement("ALTER TABLE products MODIFY category VARCHAR(50) NOT NULL");
    }

    public function down(): void
    {
        // Optionally revert to enum (with all valid values)
        DB::statement("ALTER TABLE products MODIFY category ENUM('buffet', 'budget_meals', 'budget_snacks', 'snacks', 'drinks') NOT NULL");
    }
};
