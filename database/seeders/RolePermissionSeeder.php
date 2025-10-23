<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Role and User Management
            'can.view.roles',
            'can.create.roles',
            'can.edit.roles',
            'can.delete.roles',
            'can.assign.permission',
            'can.view.users',
            'can.manage.students',
            'can.manage.lecturers',
            
            // Dashboard Access
            'can.view.dashboard',
            'can.access.superadmin',
            'can.access.student.dashboard',
            'can.access.lecturer.dashboard',
            
            // Location Management
            'can.view.locations',
            'can.create.locations',
            'can.edit.locations',
            'can.delete.locations',
            'can.manage.locations',
            'can.capture.locations',
            
            // Class Management
            'can.view.classes',
            'can.create.classes',
            'can.edit.classes',
            'can.delete.classes',
            'can.manage.classes',
            'can.view.class.students',
            'can.manage.class.attendance',
            'can.mark.attendance',
            'can.view.attendance.reports',
            
            // Student Management
            'can.view.student.list',
            'can.view.student.profile',
            'can.edit.student.profile',
            'can.view.student.attendance',
            'can.promote.students',
            
            // Department Management
            'can.view.departments',
            'can.create.departments',
            'can.edit.departments',
            'can.delete.departments',
            'can.manage.departments',
            
            // Complaint Management
            'can.view.complaints',
            'can.create.complaints',
            'can.edit.complaints',
            'can.delete.complaints',
            'can.manage.complaints',
            'can.resolve.complaints',
            
            // Profile Management
            'can.view.own.profile',
            'can.edit.own.profile',
            'can.view.other.profiles',
            'can.edit.other.profiles',
            
            // System Administration
            'can.access.admin.panel',
            'can.manage.system.settings',
            'can.view.system.logs',
            'can.manage.permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $lecturerRole = Role::firstOrCreate(['name' => 'lecturer']);

        // Assign all permissions to superadmin
        $superadminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to student
        $studentRole->givePermissionTo([
            'can.view.dashboard',
            'can.access.student.dashboard',
            'can.view.locations',
            'can.view.classes',
            'can.mark.attendance',
            'can.view.own.profile',
            'can.edit.own.profile',
            'can.create.complaints',
            'can.view.complaints',
            'can.view.student.attendance',
        ]);

        // Assign specific permissions to lecturer
        $lecturerRole->givePermissionTo([
            'can.view.dashboard',
            'can.access.lecturer.dashboard',
            'can.manage.students',
            'can.view.locations',
            'can.create.locations',
            'can.edit.locations',
            'can.delete.locations',
            'can.capture.locations',
            'can.manage.locations',
            'can.view.classes',
            'can.create.classes',
            'can.edit.classes',
            'can.delete.classes',
            'can.manage.classes',
            'can.view.class.students',
            'can.manage.class.attendance',
            'can.view.attendance.reports',
            'can.view.student.list',
            'can.view.student.profile',
            'can.view.student.attendance',
            'can.view.own.profile',
            'can.edit.own.profile',
            'can.view.other.profiles',
        ]);

        // Create a default superadmin user
        $superadmin = User::firstOrCreate(
            ['email' => 'admin@bouesti.edu.ng'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );

        $superadmin->assignRole('superadmin');
    }
}
