<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Course management
            'view courses', 'create courses', 'edit courses', 'delete courses', 'publish courses',

            // Block management
            'view blocks', 'create blocks', 'edit blocks', 'delete blocks',

            // Topic management
            'view topics', 'create topics', 'edit topics', 'delete topics',

            // Content management
            'view content', 'manage content', 'publish content', 'edit any content', 'upload files',

            // User management
            'view users', 'create users', 'edit users', 'delete users', 'assign roles',

            // Test management
            'view tests', 'create tests', 'edit tests', 'delete tests', 'take tests',
            'view test results', 'grade tests', 'review practical cases',

            // Question management
            'view questions', 'create questions', 'edit questions', 'delete questions', 'review challenges',

            // Tutoring
            'ask questions', 'answer questions', 'assign tutors',

            // Battles
            'view battles', 'create battles', 'join battles',

            // Schedule management
            'view schedules', 'create schedules', 'edit schedules', 'delete schedules',

            // Subscription management
            'view subscriptions', 'manage subscriptions', 'process refunds',

            // Announcements
            'view announcements', 'create announcements', 'manage announcements',

            // Reviews and challenges
            'manage reviews', 'view reviews', 'view students', 'view progress',

            // Certificates
            'issue certificates', 'verify certificates',

            // Admin
            'manage settings', 'view reports', 'view analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(Permission::all());

        $contentManager = Role::firstOrCreate(['name' => 'Content Manager']);
        $contentManager->givePermissionTo([
            // Content
            'view courses', 'create courses', 'edit courses', 'publish courses',
            'view blocks', 'create blocks', 'edit blocks',
            'view topics', 'create topics', 'edit topics',
            'manage content', 'publish content', 'edit any content', 'upload files',

            // Tests
            'view tests', 'create tests', 'edit tests', 'view test results',
            'view questions', 'create questions', 'edit questions', 'delete questions',
            'review challenges',

            // Schedules
            'view schedules', 'create schedules', 'edit schedules', 'delete schedules',

            // Announcements
            'view announcements', 'create announcements', 'manage announcements',

            // Users
            'view users', 'view students', 'view reports', 'view analytics',
            'manage reviews', 'view reviews',
        ]);

        $teacher = Role::firstOrCreate(['name' => 'Teacher']);
        $teacher->givePermissionTo([
            // View content
            'view courses', 'view blocks', 'view topics', 'view content',

            // Tutoring
            'answer questions',

            // Practical cases
            'review practical cases', 'grade tests',

            // Battles
            'view battles',
        ]);

        $student = Role::firstOrCreate(['name' => 'Student']);
        $student->givePermissionTo([
            // View content
            'view courses', 'view blocks', 'view topics', 'view content',

            // Tests
            'take tests', 'view test results', 'view progress',

            // Tutoring
            'ask questions',

            // Battles
            'create battles', 'join battles', 'view battles',

            // Schedules
            'view schedules',

            // Subscriptions
            'view subscriptions',

            // Certificates
            'verify certificates',

            // Announcements
            'view announcements',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Created roles: Admin, Content Manager, Teacher, Student');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}
