<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class InitialAdminSeeder extends Seeder
{
    /**
     * Seed the initial admin account when none exists.
     */
    public function run(): void
    {
        if (User::query()->whereRaw('LOWER(user_type) = ?', ['admin'])->exists()) {
            return;
        }

        $department = Department::first();
        $section = Section::where('department_id', $department?->id)->first();

        if (!$department || !$section) {
            return;
        }

        User::withoutEvents(function () use ($department, $section): void {
            User::create([
                'full_name' => env('INITIAL_ADMIN_FULL_NAME', 'System Administrator'),
                'username' => env('INITIAL_ADMIN_USERNAME', 'admin'),
                'email' => env('INITIAL_ADMIN_EMAIL', 'admin@support-portal.local'),
                'password' => hash('sha256', env('INITIAL_ADMIN_PASSWORD', 'Admin@123!')),
                'department_id' => $department->id,
                'section_id' => $section->id,
                'ip_address' => env('INITIAL_ADMIN_IP_ADDRESS', '127.0.0.1'),
                'user_type' => 'admin',
                'is_active' => true,
                'is_sudo' => true,
            ]);
        });
    }
}
