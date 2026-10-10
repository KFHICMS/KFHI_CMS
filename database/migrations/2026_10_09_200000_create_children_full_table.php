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
        $table->string('name_en');
        $table->string('name_kr')->nullable();
        $table->string('alias')->nullable();
        $table->date('birthday')->nullable();
        $table->integer('age')->nullable();
        $table->enum('gender', ['male', 'female', 'other'])->nullable();
        $table->string('religion')->nullable();
        $table->string('area')->nullable();
        $table->string('office_code')->nullable();
        $table->string('office_name')->nullable();
        $table->string('service_state')->nullable();
        $table->string('sponsor_state')->nullable();
        $table->string('guardian_type')->nullable();
        $table->string('caregiver')->nullable();
        $table->string('curriculum')->nullable();
        $table->string('grade')->nullable();
        $table->string('favorite_subject')->nullable();
        $table->enum('pass_fail', ['pass', 'fail'])->default('pass');
        $table->string('dream')->nullable();
        $table->text('dream_description')->nullable();
        $table->string('favorite_activity')->nullable();
        $table->string('health')->nullable();
        $table->text('health_description')->nullable();
        $table->string('disability_type')->nullable();
        $table->text('disability_description')->nullable();
        $table->string('qr_code_path')->nullable(); // store generated QR file path
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
?>
