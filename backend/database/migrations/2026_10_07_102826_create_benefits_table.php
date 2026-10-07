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
    Schema::create('benefits', function (Blueprint $table) {
        $table->id();
        $table->foreignId('child_id')->constrained()->cascadeOnDelete();
        $table->foreignId('benefit_type_id')->constrained()->cascadeOnDelete();
        $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
        $table->integer('quantity')->default(1);
        $table->text('notes')->nullable();
        $table->foreignId('given_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('given_at')->useCurrent();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefits');
    }
};
