<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE estimates MODIFY quantity DECIMAL(12, 2) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE estimates MODIFY quantity INT NULL');
    }
};
