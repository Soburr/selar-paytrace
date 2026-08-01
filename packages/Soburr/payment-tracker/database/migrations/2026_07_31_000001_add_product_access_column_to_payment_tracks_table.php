<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->timestamp('product_access_confirmed_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_tracks', function (Blueprint $table) {
            $table->dropColumn('product_access_confirmed_at');
        });
    }
};