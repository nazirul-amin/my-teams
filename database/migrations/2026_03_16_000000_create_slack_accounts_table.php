<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('slack_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slack_user_id')->unique();
            $table->string('slack_team_id')->nullable();
            $table->string('slack_team_name')->nullable();
            $table->string('slack_name')->nullable();
            $table->string('slack_email')->nullable();
            $table->string('slack_avatar')->nullable();
            $table->text('access_token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slack_accounts');
    }
};
