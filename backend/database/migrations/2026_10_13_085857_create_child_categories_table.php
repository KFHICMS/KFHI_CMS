<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->string('category', 20);   // education | health | nutrition | shelter
            $table->timestamps();

            $table->unique(['child_id', 'category']);   // a child is counted once per category
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_categories');
    }
};