<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. All permissions in the system
        $permissions = [
            // User management
            'manage_users',
            // Child management
            'view_children',
            'create_child',
            'edit_child',
            'archive_child',
            'view_child_health',        // sensitive
            'view_child_emergency',     // sensitive
            // QR
            'generate_qr',
            'scan_qr',
            // Programs & activities
            'manage_programs',
            'manage_activities',
            'record_attendance',
            'record_benefits',
            'record_notes',
            // Benefits & follow-ups
            'manage_benefits',
            'manage_followups',
            // CARP (Child Annual Progress) survey
            'submit_carp',              // enumerator submits collected data
            'view_carp',                // officers/admin review submissions
            // Oversight
            'view_reports',
            'view_audit_logs',
            'manage_backups',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // 2. Roles + their permissions

        // ADMIN — full access
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        // CHILD OFFICER — manages child records, follow-ups, reviews CARP
        $childOfficer = Role::firstOrCreate(['name' => 'child_officer']);
        $childOfficer->syncPermissions([
            'view_children', 'create_child', 'edit_child', 'archive_child',
            'view_child_health', 'view_child_emergency',
            'generate_qr', 'scan_qr',
            'manage_followups', 'view_carp', 'view_reports',
        ]);

        // FIELD OFFICER — field work: scan + record only
        $fieldOfficer = Role::firstOrCreate(['name' => 'field_officer']);
        $fieldOfficer->syncPermissions([
            'view_children', 'scan_qr',
            'record_attendance', 'record_benefits', 'record_notes',
        ]);

        // SURVEY ENUMERATOR — temporary, CARP data collection only
        $enumerator = Role::firstOrCreate(['name' => 'survey_enumerator']);
        $enumerator->syncPermissions([
            'view_children',   // identify the child only (basic fields)
            'scan_qr',         // scan to find the right child
            'submit_carp',     // submit collected child + guardian survey data
        ]);

        // 3. Default admin user for testing (DEV ONLY — change before production)
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@kfhi.test'],
            ['name' => 'KFHI Admin', 'password' => Hash::make('Admin@12345')]
        );
        $adminUser->assignRole('admin');
    }
}

