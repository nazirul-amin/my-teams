<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_cards', function (Blueprint $table) {
            $table->boolean('is_dark_mode')->default(false)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('contact_cards', function (Blueprint $table) {
            $table->dropColumn('is_dark_mode');
        });
    }
};
