@extends('layout.app')

@section('content')
<div class="row">
      {{-- LEFT: FORM --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">Add Personnel Leave</div>
            <div class="card-body">
                <form id="leave-form" autocomplete="off">
                    @csrf

                    {{-- Employee: only from same department --}}
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Select Employee --</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Department: fixed to current user’s department --}}
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" value="{{ $department?->name }}" disabled>
                        <input type="hidden" name="department_id" value="{{ $department?->id }}">
                    </div>

                    {{-- Section: only those under the current user's department --}}
                    <div class="mb-3">
                        <label class="form-label">Section</label>
                        <select name="section_id" id="section_id" class="form-select" required>
                            <option value="">-- Select Section --</option>
                            @foreach($sections as $s)
                                <option value="{{ $s->id }}"
                                    {{ (string) old('section_id', auth()->user()->section_id ?? '') === (string) $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" name="reason" class="form-control" maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Leave Address</label>
                        <input type="text" name="leave_address" class="form-control" maxlength="255">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Encoded By</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->full_name ?? '' }}" disabled>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save</button>
                </form>
            </div>
        </div>
    </div>

    {{-- RIGHT: TABLE (only current department) --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                Personnel On / Upcoming Leave
                <span class="text-muted ms-2">(as of {{ \Illuminate\Support\Carbon::parse($today)->format('M d, Y') }})</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                            <th>Name</th>
                            <th>Section</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Leave Address</th>
                            </tr>
                        </thead>
                        <tbody id="leave-table-body">
                            @forelse($leaves as $leave)
                            @php
                                $today = now('Asia/Manila')->toDateString();
                                $isOnLeaveNow = $today >= $leave->from_date->toDateString() && $today <= $leave->to_date->toDateString();
                            @endphp
                            <tr>
                                <td>{{ $leave->user?->full_name }}</td>
                                <td>{{ $leave->section?->name }}</td>
                                <td>{{ $leave->from_date->format('M d, Y') }}</td>
                                <td>{{ $leave->to_date->format('M d, Y') }}</td>
                                <td>
                                @if($isOnLeaveNow)
                                    <span class="badge bg-danger">On Leave</span>
                                @else
                                    <span class="badge bg-warning text-dark">Upcoming</span>
                                @endif
                                </td>
                                <td>{{ $leave->reason }}</td>
                                <td>{{ $leave->leave_address }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No personnel currently on or scheduled for leave in your department.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer small text-muted">
            Filtered to your department ({{ $department?->name }}). Past leaves (to_date &lt; today) are hidden.
            </div>
        </div>
    </div>
</div>
    @section('js-custom')

    <script>
        const LEAVE_STORE_URL = "{{ route('leave-monitoring.store') }}";
    </script>

        <script src="{{ asset('assets/js/custom/from-to-date.js') }}"></script>
        <script src="{{ asset('assets/js/custom/leave-monitoring.js') }}"></script>

    @endsection

@endsection
