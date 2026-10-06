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
    Schema::create('qr_tokens', function (Blueprint $table) {
        $table->id();
        $table->foreignId('child_id')->constrained()->cascadeOnDelete();
        $table->string('token', 64)->unique();   // random reference, NOT child data
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

};
