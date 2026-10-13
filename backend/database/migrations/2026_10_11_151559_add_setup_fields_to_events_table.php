<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Events: program link, start/end date-time, location, and who is assigned.
 * ADDITIVE ONLY – nothing existing is changed or dropped.
 * `event_date` is kept (reports/older code may use it); start_at is back-filled from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('description')->constrained('programs')->nullOnDelete();
            $table->dateTime('start_at')->nullable()->after('event_date');
            $table->dateTime('end_at')->nullable()->after('start_at');
            $table->string('location')->nullable()->after('end_at');
            $table->index('status');
        });

        // copy the existing date into start_at (non-destructive: event_date stays as it is)
        DB::table('events')->whereNotNull('event_date')->whereNull('start_at')
            ->update(['start_at' => DB::raw('event_date')]);

        // officers / enumerators assigned to an event
        Schema::create('event_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_user');

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn(['start_at', 'end_at', 'location']);
        });
    }
};