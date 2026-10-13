<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the KFHI child-record fields. ADDITIVE ONLY: every new column is nullable,
 * nothing existing is changed, renamed, copied or dropped.
 *
 * Mapping to the 26-field specification
 *   Child Code ................ child_code            (existing)
 *   Child Name (English) ...... full_name             (existing)
 *   Birthday .................. date_of_birth         (existing)
 *   Age ....................... calculated from date_of_birth (no column)
 *   Gender / Grade ............ gender / grade        (existing)
 *   Health Description ........ medical_info          (existing, encrypted – reused, not duplicated)
 *   everything else ........... new columns below
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->string('name_korean')->nullable()->after('full_name');
            $table->string('alias')->nullable()->after('name_korean');
            $table->string('religion', 100)->nullable()->after('gender');
            $table->string('area', 150)->nullable()->after('address');
            $table->string('gnd', 150)->nullable()->after('area');            // Grama Niladhari Division (ID card)
            $table->string('office_code', 50)->nullable()->after('gnd');
            $table->string('office_name', 150)->nullable()->after('office_code');
            $table->string('service_state', 100)->nullable()->after('office_name');
            $table->string('sponsor_state', 100)->nullable()->after('service_state');
            $table->string('guardian_type', 100)->nullable()->after('sponsor_state');
            $table->string('caregiver')->nullable()->after('guardian_type');
            $table->string('curriculum', 150)->nullable()->after('caregiver');
            $table->string('favorite_subject', 150)->nullable()->after('grade');
            $table->string('pass_fail', 10)->nullable()->after('favorite_subject');
            $table->string('dream')->nullable()->after('pass_fail');
            $table->text('dream_description')->nullable()->after('dream');
            $table->string('favorite_activity')->nullable()->after('dream_description');

            // SENSITIVE – stored encrypted (see Child model casts)
            $table->text('health')->nullable()->after('favorite_activity');
            $table->text('disability_type')->nullable()->after('medical_info');
            $table->text('disability_description')->nullable()->after('disability_type');

            // filters used by the child list
            $table->index('area');
            $table->index('office_code');
            $table->index('service_state');
            $table->index('sponsor_state');
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropIndex(['office_code']);
            $table->dropIndex(['service_state']);
            $table->dropIndex(['sponsor_state']);

            $table->dropColumn([
                'name_korean', 'alias', 'religion', 'area', 'gnd', 'office_code', 'office_name',
                'service_state', 'sponsor_state', 'guardian_type', 'caregiver', 'curriculum',
                'favorite_subject', 'pass_fail', 'dream', 'dream_description', 'favorite_activity',
                'health', 'disability_type', 'disability_description',
            ]);
        });
    }
};