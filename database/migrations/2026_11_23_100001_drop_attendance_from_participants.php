<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('participants') && Schema::hasColumn('participants', 'attended')) {
            Schema::table('participants', function (Blueprint $table): void {
                $table->dropIndex(['attended']);
                $table->dropColumn(['attended', 'attended_at']);
            });
        }

        Schema::dropIfExists('attendance_settings');
    }

    public function down(): void
    {
        if (Schema::hasTable('participants') && ! Schema::hasColumn('participants', 'attended')) {
            Schema::table('participants', function (Blueprint $table): void {
                $table->boolean('attended')->default(false)->after('privacy_agreed');
                $table->timestamp('attended_at')->nullable()->after('attended');
                $table->index('attended');
            });
        }

        if (! Schema::hasTable('attendance_settings')) {
            Schema::create('attendance_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('spreadsheet_id')->nullable();
                $table->string('sheet_range')->default('Attendance!A:C');
                $table->timestamps();
            });
        }
    }
};
