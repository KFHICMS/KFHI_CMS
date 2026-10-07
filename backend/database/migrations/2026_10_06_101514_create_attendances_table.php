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
    Schema::create('attendances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('event_id')->constrained()->cascadeOnDelete();
        $table->foreignId('child_id')->constrained()->cascadeOnDelete();
        $table->string('status')->default('present');
        $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->unique(['event_id', 'child_id']); // 🔑 prevents duplicate/redundant attendance
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
