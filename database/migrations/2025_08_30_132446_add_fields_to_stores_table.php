<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->text('description')->nullable()->after('business_type');
            $table->text('address')->nullable()->after('description');
            $table->string('contact_number')->nullable()->after('address');
            $table->text('operating_hours')->nullable()->after('contact_number');
            $table->string('store_image')->nullable()->after('operating_hours');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['description', 'address', 'contact_number', 'operating_hours', 'store_image']);
        });
    }
};