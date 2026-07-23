<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Driver;
use App\Models\Section;
use App\Models\Vehicle;
use App\Models\TripTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TripTicketWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
        URL::forceScheme('http');
    }

    public function test_request_encode_api_approve_and_print_flow(): void
    {
        [$requester, $encoder, $approver] = $this->tripTicketUsers();

        $createResponse = $this->actingAs($requester)->post('/trip-tickets', [
            'purpose' => 'Official business meeting',
            'destination_mode' => 'local_maramag',
            'local_destination' => 'Main Office',
            'requested_start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
            'requested_end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'passengers' => 'Requester One',
            'contact_number' => '09170000000',
            'remarks' => 'Feature test request',
        ]);

        $ticket = TripTicket::query()->latest('id')->firstOrFail();

        $createResponse->assertRedirect('/trip-tickets/' . $ticket->id);
        $this->assertSame(TripTicket::STATUS_PENDING_DETAILS, $ticket->status);
        $this->assertSame('Main Office, Maramag, Bukidnon, Philippines', $ticket->destination);
        $this->assertNull($ticket->trip_ticket_location_id);
        $this->assertSame(0.0, $ticket->distance_km);

        $vehicle = Vehicle::create([
            'description' => 'Test Vehicle',
            'plate_number' => 'ABC-123',
            'is_available' => true,
        ]);
        $driver = Driver::create([
            'name' => 'Test Driver',
            'is_active' => true,
        ]);

        $this->actingAs($encoder)
            ->post('/trip-tickets/' . $ticket->id . '/encode', [
                'vehicle_id' => $vehicle->id,
                'driver_id' => $driver->id,
                'actual_departure_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
                'actual_return_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
                'remarks' => 'Encoded from feature test',
            ])
            ->assertRedirect('/trip-tickets/' . $ticket->id);

        $ticket->refresh();
        $this->assertSame(TripTicket::STATUS_FOR_APPROVAL, $ticket->status);
        $this->assertSame($vehicle->id, $ticket->vehicle_id);
        $this->assertSame('ABC-123 - Test Vehicle', $ticket->vehicle_details);
        $this->assertSame($driver->id, $ticket->driver_id);
        $this->assertSame('Test Driver', $ticket->driver_name);

        Sanctum::actingAs($approver);

        $this->getJson('/api/trip-tickets/for-approval')
            ->assertOk()
            ->assertJsonFragment(['id' => $ticket->id])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'ticket_number',
                        'status',
                        'destination',
                        'purpose',
                        'vehicle_details',
                        'driver_name',
                        'requester',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->postJson('/api/trip-tickets/' . $ticket->id . '/approve', [
            'approval_remarks' => 'Approved from feature test',
        ])
            ->assertOk()
            ->assertJsonPath('ticket.status', TripTicket::STATUS_APPROVED)
            ->assertJsonPath('ticket.approval_remarks', 'Approved from feature test');

        $ticket->refresh();
        $this->assertSame(TripTicket::STATUS_APPROVED, $ticket->status);
        $this->assertNotNull($ticket->approved_by);

        $this->actingAs($requester)
            ->get('/trip-tickets/' . $ticket->id . '/print')
            ->assertOk()
            ->assertSee('Trip Ticket')
            ->assertSee('Approved')
            ->assertSee('ABC-123 - Test Vehicle')
            ->assertSee('Test Driver');

        $this->assertSame(3, $ticket->logs()->count());
    }

    public function test_regular_user_cannot_print_unapproved_ticket(): void
    {
        [$requester] = $this->tripTicketUsers();

        $ticket = TripTicket::create([
            'requested_by' => $requester->id,
            'department_id' => $requester->department_id,
            'section_id' => $requester->section_id,
            'purpose' => 'Print guard test',
            'destination' => 'Main Office',
            'requested_start_datetime' => now()->addDay(),
            'requested_end_datetime' => now()->addDay()->addHour(),
            'status' => TripTicket::STATUS_PENDING_DETAILS,
        ]);

        $this->actingAs($requester)
            ->get('/trip-tickets/' . $ticket->id . '/print')
            ->assertForbidden();
    }

    public function test_non_approver_cannot_use_approval_api(): void
    {
        [$requester] = $this->tripTicketUsers();

        $ticket = TripTicket::create([
            'requested_by' => $requester->id,
            'department_id' => $requester->department_id,
            'section_id' => $requester->section_id,
            'purpose' => 'API guard test',
            'destination' => 'Main Office',
            'requested_start_datetime' => now()->addDay(),
            'requested_end_datetime' => now()->addDay()->addHour(),
            'vehicle_details' => 'Vehicle',
            'driver_name' => 'Driver',
            'status' => TripTicket::STATUS_FOR_APPROVAL,
        ]);

        Sanctum::actingAs($requester);

        $this->postJson('/api/trip-tickets/' . $ticket->id . '/approve', [
            'approval_remarks' => 'Should fail',
        ])->assertForbidden();
    }

    public function test_dispatcher_can_manage_trip_ticket_drivers(): void
    {
        [, $dispatcher] = $this->tripTicketUsers();

        $this->actingAs($dispatcher)
            ->get('/trip-tickets')
            ->assertOk()
            ->assertSee('Manage Drivers')
            ->assertSee('Manage Vehicles');

        $this->actingAs($dispatcher)
            ->post('/trip-tickets/drivers', [
                'name' => 'CRUD Test Driver',
            ])
            ->assertRedirect('/trip-tickets');

        $driver = Driver::query()->where('name', 'CRUD Test Driver')->firstOrFail();

        $this->actingAs($dispatcher)
            ->get('/trip-tickets')
            ->assertOk()
            ->assertSee('CRUD Test Driver');

        $this->actingAs($dispatcher)
            ->put('/trip-tickets/drivers/' . $driver->id, [
                'name' => 'Updated CRUD Test Driver',
            ])
            ->assertRedirect('/trip-tickets');

        $this->assertDatabaseHas('drivers', [
            'id' => $driver->id,
            'name' => 'Updated CRUD Test Driver',
        ]);

        $this->actingAs($dispatcher)
            ->delete('/trip-tickets/drivers/' . $driver->id)
            ->assertRedirect('/trip-tickets');

        $this->assertDatabaseMissing('drivers', [
            'id' => $driver->id,
        ]);
    }

    public function test_dispatcher_can_manage_trip_ticket_vehicles(): void
    {
        [, $dispatcher] = $this->tripTicketUsers();

        $this->actingAs($dispatcher)
            ->post('/trip-tickets/vehicles', [
                'description' => 'CRUD Test Model',
                'plate_number' => 'CRUD-123',
            ])
            ->assertRedirect('/trip-tickets');

        $vehicle = Vehicle::query()->where('plate_number', 'CRUD-123')->firstOrFail();
        TripTicket::query()->where('vehicle_id', $vehicle->id)->update(['vehicle_id' => null]);

        $this->actingAs($dispatcher)
            ->get('/trip-tickets')
            ->assertOk()
            ->assertSee('CRUD Test Model')
            ->assertSee('CRUD-123');

        $this->actingAs($dispatcher)
            ->put('/trip-tickets/vehicles/' . $vehicle->id, [
                'description' => 'Updated CRUD Test Model',
                'plate_number' => 'CRUD-456',
            ])
            ->assertRedirect('/trip-tickets');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'description' => 'Updated CRUD Test Model',
            'plate_number' => 'CRUD-456',
        ]);

        $this->actingAs($dispatcher)
            ->delete('/trip-tickets/vehicles/' . $vehicle->id)
            ->assertRedirect('/trip-tickets');

        $this->assertDatabaseMissing('vehicles', [
            'id' => $vehicle->id,
        ]);
    }

    public function test_regular_user_cannot_manage_trip_ticket_drivers_or_vehicles(): void
    {
        [$requester] = $this->tripTicketUsers();

        $this->actingAs($requester)
            ->get('/trip-tickets/drivers')
            ->assertForbidden();

        $this->actingAs($requester)
            ->get('/trip-tickets/vehicles')
            ->assertForbidden();
    }

    public function test_mobile_login_returns_token_and_trip_ticket_permissions(): void
    {
        [, , $approver] = $this->tripTicketUsers();

        $this->postJson('/api/login', [
            'username' => $approver->username,
            'password' => 'password',
            'device_name' => 'feature-test-device',
        ])
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.username', $approver->username)
            ->assertJsonPath('user.permissions.can_approve_trip_tickets', true)
            ->assertJsonStructure([
                'access_token',
                'user' => [
                    'id',
                    'full_name',
                    'username',
                    'permissions',
                ],
            ]);
    }

    public function test_gatekeeper_records_and_validates_odometer_readings(): void
    {
        [$gatekeeper] = $this->tripTicketUsers();
        User::withoutEvents(fn () => $gatekeeper->update([
            'can_gatekeep_trip_tickets' => true,
        ]));

        $ticket = TripTicket::create([
            'requested_by' => $gatekeeper->id,
            'department_id' => $gatekeeper->department_id,
            'section_id' => $gatekeeper->section_id,
            'purpose' => 'Odometer workflow test',
            'destination' => 'Main Office',
            'requested_start_datetime' => now()->subHour(),
            'requested_end_datetime' => now()->addHours(2),
            'vehicle_details' => 'Test vehicle',
            'driver_name' => 'Test driver',
            'status' => TripTicket::STATUS_APPROVED,
        ]);

        Sanctum::actingAs($gatekeeper);

        $this->postJson('/api/trip-tickets/gatekeeper/' . $ticket->id . '/departure', [
            'actual_departure_datetime' => now()->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors('departure_odometer');

        $departureTime = now()->subMinutes(30);
        $this->postJson('/api/trip-tickets/gatekeeper/' . $ticket->id . '/departure', [
            'actual_departure_datetime' => $departureTime->toIso8601String(),
            'departure_odometer' => 125430.5,
            'remarks' => 'Departure reading verified.',
        ])
            ->assertOk()
            ->assertJsonPath('ticket.status', TripTicket::STATUS_DISPATCHED)
            ->assertJsonPath('ticket.departure_odometer', 125430.5);

        $ticket->refresh();
        $this->assertSame(125430.5, $ticket->departure_odometer);

        $this->postJson('/api/trip-tickets/gatekeeper/' . $ticket->id . '/return', [
            'actual_return_datetime' => now()->toIso8601String(),
            'return_odometer' => 125400,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Return odometer cannot be lower than departure odometer.');

        $this->postJson('/api/trip-tickets/gatekeeper/' . $ticket->id . '/return', [
            'actual_return_datetime' => now()->toIso8601String(),
            'return_odometer' => 125612.5,
            'remarks' => 'Return reading verified.',
        ])
            ->assertOk()
            ->assertJsonPath('ticket.status', TripTicket::STATUS_COMPLETED)
            ->assertJsonPath('ticket.return_odometer', 125612.5);

        $ticket->refresh();
        $this->assertSame(125612.5, $ticket->return_odometer);
        $this->assertSame(182.0, $ticket->return_odometer - $ticket->departure_odometer);
    }

    /**
     * @return array{0: User, 1: User, 2: User}
     */
    protected function tripTicketUsers(): array
    {
        $department = Department::firstOrCreate(
            ['code' => 'TTT'],
            ['name' => 'Trip Ticket Test Department']
        );

        $section = Section::firstOrCreate(
            ['code' => 'TTT'],
            [
                'name' => 'Trip Ticket Test Section',
                'department_id' => $department->id,
            ]
        );

        $users = User::withoutEvents(fn () => [
            User::updateOrCreate(
                ['username' => 'tt_requester_feature'],
                [
                    'full_name' => 'Trip Ticket Requester Feature',
                    'password' => hash('sha256', 'password'),
                    'department_id' => $department->id,
                    'section_id' => $section->id,
                    'ip_address' => '127.0.0.1',
                    'user_type' => 'user',
                    'is_active' => true,
                    'can_encode_trip_tickets' => false,
                    'can_approve_trip_tickets' => false,
                    'can_manage_trip_tickets' => false,
                ]
            ),
            User::updateOrCreate(
                ['username' => 'tt_encoder_feature'],
                [
                    'full_name' => 'Trip Ticket Encoder Feature',
                    'password' => hash('sha256', 'password'),
                    'department_id' => $department->id,
                    'section_id' => $section->id,
                    'ip_address' => '127.0.0.1',
                    'user_type' => 'user',
                    'is_active' => true,
                    'can_encode_trip_tickets' => true,
                    'can_approve_trip_tickets' => false,
                    'can_manage_trip_tickets' => false,
                ]
            ),
            User::updateOrCreate(
                ['username' => 'tt_approver_feature'],
                [
                    'full_name' => 'Trip Ticket Approver Feature',
                    'password' => hash('sha256', 'password'),
                    'department_id' => $department->id,
                    'section_id' => $section->id,
                    'ip_address' => '127.0.0.1',
                    'user_type' => 'user',
                    'is_active' => true,
                    'can_encode_trip_tickets' => false,
                    'can_approve_trip_tickets' => true,
                    'can_manage_trip_tickets' => false,
                ]
            ),
        ]);

        return $users;
    }
}
