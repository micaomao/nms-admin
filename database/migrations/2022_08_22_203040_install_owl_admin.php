<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        \Micaomao\NmsAdmin\Support\Cores\Database::make()->up();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        \Micaomao\NmsAdmin\Support\Cores\Database::make()->down();
    }
};
