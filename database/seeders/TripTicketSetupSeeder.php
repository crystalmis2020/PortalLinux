<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;

class TripTicketSetupSeeder extends Seeder
{
    /**
     * Seed development accounts for the trip ticket workflow.
     */
    public function run(): void
    {
        $defaultPassword = env('TRIP_TICKET_DEFAULT_PASSWORD');

        if (! is_string($defaultPassword) || $defaultPassword === '') {
            throw new \RuntimeException(
                'TRIP_TICKET_DEFAULT_PASSWORD must be set before running TripTicketSetupSeeder.'
            );
        }

        $department = Department::firstOrCreate(
            ['code' => 'ADMIN'],
            ['name' => 'Administration']
        );

        $section = Section::firstOrCreate(
            ['code' => 'MIS'],
            [
                'name' => 'Management Information System',
                'department_id' => $department->id,
            ]
        );

        $this->upsertTripTicketUser(
            username: env('TRIP_TICKET_ENCODER_USERNAME', 'trip_encoder'),
            fullName: env('TRIP_TICKET_ENCODER_NAME', 'Trip Ticket Encoder'),
            departmentId: $department->id,
            sectionId: $section->id,
            permissions: ['can_encode_trip_tickets' => true],
            password: $defaultPassword
        );

        $this->upsertTripTicketUser(
            username: env('TRIP_TICKET_APPROVER_USERNAME', 'trip_approver'),
            fullName: env('TRIP_TICKET_APPROVER_NAME', 'Trip Ticket Approver'),
            departmentId: $department->id,
            sectionId: $section->id,
            permissions: ['can_approve_trip_tickets' => true],
            password: $defaultPassword
        );


        $this->upsertTripTicketUser(
            username: env('TRIP_TICKET_GATEKEEPER_USERNAME', 'trip_gatekeeper'),
            fullName: env('TRIP_TICKET_GATEKEEPER_NAME', 'Trip Ticket Gatekeeper'),
            departmentId: $department->id,
            sectionId: $section->id,
            permissions: ['can_gatekeep_trip_tickets' => true],
            password: $defaultPassword
        );
    }

    protected function upsertTripTicketUser(
        string $username,
        string $fullName,
        int $departmentId,
        int $sectionId,
        array $permissions,
        string $password
    ): void {
        User::withoutEvents(function () use ($username, $fullName, $departmentId, $sectionId, $permissions, $password): void {
            User::updateOrCreate(
                ['username' => $username],
                array_merge([
                    'full_name' => $fullName,
                    'password' => hash('sha256', $password),
                    'department_id' => $departmentId,
                    'section_id' => $sectionId,
                    'ip_address' => '127.0.0.1',
                    'user_type' => 'user',
                    'is_active' => true,
                ], $permissions)
            );
        });
    }
}
