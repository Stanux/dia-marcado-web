<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE user_social_accounts ALTER COLUMN avatar TYPE TEXT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE user_social_accounts ALTER COLUMN avatar TYPE VARCHAR(255)');
    }
};
