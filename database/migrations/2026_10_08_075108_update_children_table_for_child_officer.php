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
        Schema::table('children', function (Blueprint $table) {
            $table->string('name_english')->nullable();
            $table->string('name_korean')->nullable();
            $table->string('alias')->nullable();
            
            $table->string('religion')->nullable();
            $table->string('area')->nullable();
            $table->string('office_code')->nullable();
            $table->string('office_name')->nullable();
            
            $table->string('service_state')->nullable();
            $table->string('sponsor_state')->nullable();
            $table->string('guardian_type')->nullable();
            $table->string('caregiver')->nullable();
            
            $table->string('curriculum')->nullable();
            $table->string('favorite_subject')->nullable();
            $table->string('pass_fail')->nullable();
            
            $table->string('dream')->nullable();
            $table->text('dream_description')->nullable();
            $table->string('favorite_activity')->nullable();
            
            $table->string('health')->nullable();
            $table->text('health_description')->nullable();
            $table->string('disability_type')->nullable();
            $table->text('disability_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropColumn([
                'name_english', 'name_korean', 'alias', 'religion', 'area',
                'office_code', 'office_name', 'service_state', 'sponsor_state',
                'guardian_type', 'caregiver', 'curriculum', 'favorite_subject',
                'pass_fail', 'dream', 'dream_description', 'favorite_activity',
                'health', 'health_description', 'disability_type', 'disability_description'
            ]);
        });
    }
};
