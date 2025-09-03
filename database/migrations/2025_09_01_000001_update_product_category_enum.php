<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change the enum values for the 'category' column
        DB::statement("ALTER TABLE products MODIFY category ENUM('buffet', 'budget_meals', 'budget_snacks', 'snacks', 'drinks') NOT NULL");
    }

    public function down(): void
    {
        // Optionally revert to the old enum values if needed (example: 'Meals', 'Drinks')
        DB::statement("ALTER TABLE products MODIFY category ENUM('Meals', 'Drinks') NOT NULL");
    }
};

