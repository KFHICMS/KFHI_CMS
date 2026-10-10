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
    Schema::create('guardians', function (Blueprint $table) {
        $table->id();
        $table->foreignId('child_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('relationship')->nullable();
        $table->string('contact_number')->nullable();
        $table->string('address')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
