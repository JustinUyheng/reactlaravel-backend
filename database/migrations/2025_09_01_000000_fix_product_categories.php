<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update 'Meals' to 'buffet'
        DB::table('products')->where('category', 'Meals')->update(['category' => 'buffet']);
        // Update 'Drinks' to 'drinks'
        DB::table('products')->where('category', 'Drinks')->update(['category' => 'drinks']);
    }

    public function down(): void
    {
        // Optionally revert changes (not strictly necessary)
        DB::table('products')->where('category', 'buffet')->update(['category' => 'Meals']);
        DB::table('products')->where('category', 'drinks')->update(['category' => 'Drinks']);
    }
};
