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
        Schema::table('draws', function (Blueprint $table) {
            $table->string('algorithm')->default('sorted-pool-random_int-v1')->after('prize_label');
            $table->unsignedInteger('pool_count')->default(0)->after('algorithm');
            $table->string('pool_hash', 64)->nullable()->after('pool_count');
            $table->unsignedInteger('selected_index')->nullable()->after('pool_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('draws', function (Blueprint $table) {
            $table->dropColumn(['algorithm', 'pool_count', 'pool_hash', 'selected_index']);
        });
    }
};
