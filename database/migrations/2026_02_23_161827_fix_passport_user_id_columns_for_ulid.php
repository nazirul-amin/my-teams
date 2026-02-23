<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE oauth_auth_codes MODIFY user_id CHAR(26) NOT NULL');
        DB::statement('ALTER TABLE oauth_access_tokens MODIFY user_id CHAR(26) NULL');
        DB::statement('ALTER TABLE oauth_device_codes MODIFY user_id CHAR(26) NULL');
        DB::statement('ALTER TABLE oauth_clients MODIFY owner_id CHAR(26) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE oauth_auth_codes MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE oauth_access_tokens MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE oauth_device_codes MODIFY user_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE oauth_clients MODIFY owner_id BIGINT UNSIGNED NULL');
    }

    /**
     * Get the migration connection name.
     */
    public function getConnection(): ?string
    {
        return $this->connection ?? config('passport.connection');
    }
};
