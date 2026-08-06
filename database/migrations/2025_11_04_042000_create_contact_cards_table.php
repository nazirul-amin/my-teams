<?php

use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_cards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignIdFor(User::class)->unique()->constrained()->nullOnDelete();
            $table->foreignIdFor(Company::class)->constrained()->nullOnDelete();
            $table->foreignIdFor(Team::class)->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_cards');
    }
};
