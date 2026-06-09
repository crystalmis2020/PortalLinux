<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Section;
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
            'destination' => 'Main Office',
            'requested_start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
            'requested_end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
            'passengers' => 'Requester One',
            'contact_number' => '09170000000',
            'remarks' => 'Feature test request',
        ]);

        $ticket = TripTicket::query()->latest('id')->firstOrFail();

        $createResponse->assertRedirect('/trip-tickets/' . $ticket->id);
        $this->assertSame(TripTicket::STATUS_PENDING_DETAILS, $ticket->status);

        $this->actingAs($encoder)
            ->post('/trip-tickets/' . $ticket->id . '/encode', [
                'vehicle_details' => 'Test Vehicle ABC-123',
                'driver_name' => 'Test Driver',
                'actual_departure_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
                'actual_return_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
                'remarks' => 'Encoded from feature test',
            ])
            ->assertRedirect('/trip-tickets/' . $ticket->id);

        $ticket->refresh();
        $this->assertSame(TripTicket::STATUS_FOR_APPROVAL, $ticket->status);
        $this->assertSame('Test Vehicle ABC-123', $ticket->vehicle_details);
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
            ->assertSee('Test Vehicle ABC-123')
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
