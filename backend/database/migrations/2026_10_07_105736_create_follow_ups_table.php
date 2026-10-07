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
    Schema::create('follow_ups', function (Blueprint $table) {
        $table->id();
        $table->foreignId('child_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('notes')->nullable();
        $table->string('status')->default('pending');   // pending | completed
        $table->date('due_date')->nullable();
        $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
