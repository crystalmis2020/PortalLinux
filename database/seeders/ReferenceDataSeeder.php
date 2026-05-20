<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Issue;
use App\Models\Section;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    /**
     * Seed the application's reference data.
     */
    public function run(): void
    {
        $department = Department::firstOrCreate(
            ['code' => 'ADMIN'],
            ['name' => 'Administration']
        );

        Section::firstOrCreate(
            ['code' => 'MIS'],
            [
                'name' => 'Management Information System',
                'department_id' => $department->id,
            ]
        );

        foreach ([
            'Hardware',
            'Software',
            'Network',
            'Printer',
            'Email',
            'Account Access',
        ] as $issueName) {
            Issue::firstOrCreate(['name' => $issueName]);
        }
    }
}
