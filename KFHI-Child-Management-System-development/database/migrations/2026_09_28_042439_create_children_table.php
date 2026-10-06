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
    Schema::create('children', function (Blueprint $table) {
        $table->id();
        $table->string('child_code')->unique();
        $table->string('full_name');
        $table->date('date_of_birth')->nullable();
        $table->string('gender')->nullable();
        $table->string('address')->nullable();
        $table->string('photo_path')->nullable();
        // Education
        $table->string('school')->nullable();
        $table->string('grade')->nullable();
        $table->string('education_status')->nullable();
        // Health (SENSITIVE)
        $table->text('medical_info')->nullable();
        $table->text('special_requirements')->nullable();
        // Emergency (SENSITIVE)
        $table->text('emergency_contacts')->nullable();
        // Program
        $table->string('program')->nullable();
        $table->date('registration_date')->nullable();
        $table->text('participation_details')->nullable();
        // Lifecycle
        $table->string('status')->default('active'); // active | archived
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
