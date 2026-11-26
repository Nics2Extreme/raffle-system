<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prizes')) {
            Schema::create('prizes', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('remaining');
                $table->string('type');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('draws') && ! Schema::hasColumn('draws', 'prize_id')) {
            Schema::table('draws', function (Blueprint $table): void {
                $table->foreignId('prize_id')
                    ->nullable()
                    ->after('drawn_by')
                    ->constrained()
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('draws') && Schema::hasColumn('draws', 'prize_id')) {
            Schema::table('draws', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('prize_id');
            });
        }

        Schema::dropIfExists('prizes');
    }
};
