@extends('layout.app')
@section('content')

<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Administrative Tool</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item" aria-current="page"><a href="{{ route('administrative.index') }}">User</a></li>
                <li class="breadcrumb-item" aria-current="page">Edit</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $user->full_name }}</li>
            </ol>
        </nav>
    </div>
</div>
<!--end breadcrumb-->

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column align-items-center text-center">
                    <img
                        src="{{ $user->profile_photo_url }}"
                        alt="{{ $user->full_name }}"
                        class="rounded-circle p-1 bg-primary"
                        width="110"
                        height="110"
                        style="object-fit: cover; cursor: pointer;"
                        data-bs-toggle="modal"
                        data-bs-target="#profilePhotoPreviewModal"
                    >
                    <div class="mt-3">
                        <h4>{{$user->full_name}}</h4>
                        <p class="text-secondary mb-1">{{ $user->department->name }}</p>
                        <p class="text-muted font-size-sm">{{ $user->ip_address }}</p>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateProfileModal">Update Profile</button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updatePasswordModal">Update Password</button>
                        {{-- <button class="btn btn-primary">Follow</button>
                        <button class="btn btn-outline-primary">Message</button> --}}
                    </div>
                </div>
                <hr class="my-4" />
                {{-- <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="mb-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-globe me-2 icon-inline"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>Website</h6>
                        <span class="text-secondary">https://codervent.com</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="mb-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-github me-2 icon-inline"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path></svg>Github</h6>
                        <span class="text-secondary">codervent</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="mb-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-twitter me-2 icon-inline text-info"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>Twitter</h6>
                        <span class="text-secondary">@codervent</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="mb-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-instagram me-2 icon-inline text-danger"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>Instagram</h6>
                        <span class="text-secondary">codervent</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <h6 class="mb-0"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-facebook me-2 icon-inline text-primary"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>Facebook</h6>
                        <span class="text-secondary">codervent</span>
                    </li>
                </ul> --}}
            </div>
        </div>
    </div>
    <div class="col-lg-8">

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="d-flex align-items-center mb-3">Reports Created</h5>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 user-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reports as $report)
                                    <tr>
                                        <td>{{ $report->id }}</td>
                                        <td>{{ $report->issueCategory->name }}</td>
                                        <td>{{ $report->status }}</td>
                                        <td>{{ $report->created_at->format('M d, Y') }}</td>
                                        <td class="text-center font-22">
                                            <a href="{{ route('reports.details', $report->id) }}"><i class="bx bx-dots-horizontal-rounded"></i></a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="profilePhotoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Profile Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img
                    src="{{ $user->profile_photo_url }}"
                    alt="{{ $user->full_name }} profile photo"
                    class="img-fluid rounded"
                    style="max-height: 70vh; object-fit: contain;"
                >
            </div>
        </div>
    </div>
</div>

<!-- update information Modal -->
<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fadeIn animated bx bx-user-plus"></i> Update Information </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="editUserForm">
                @csrf
                <input type="hidden" id="user_id" value="{{ $user->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="{{ $user->full_name }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="{{ $user->username }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department_id" required>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ $user->department_id == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section_id" required>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ $user->section_id == $section->id ? 'selected' : '' }}>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" value="{{ $user->ip_address }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="user_type" class="form-label">User Type</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="User" {{ strtolower($user->user_type ?? '') == 'user' ? 'selected' : '' }}>User</option>
                            <option value="Admin" {{ strtolower($user->user_type ?? '') == 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Trip Ticket Access</label>
                        <div class="border rounded p-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="can_encode_trip_tickets" name="can_encode_trip_tickets" {{ $user->can_encode_trip_tickets ? 'checked' : '' }}>
                                <label class="form-check-label" for="can_encode_trip_tickets">Encoder</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="can_approve_trip_tickets" name="can_approve_trip_tickets" {{ $user->can_approve_trip_tickets ? 'checked' : '' }}>
                                <label class="form-check-label" for="can_approve_trip_tickets">Approver</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="can_manage_trip_tickets" name="can_manage_trip_tickets" {{ $user->can_manage_trip_tickets ? 'checked' : '' }}>
                                <label class="form-check-label" for="can_manage_trip_tickets">Manager</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="can_gatekeep_trip_tickets" name="can_gatekeep_trip_tickets" {{ $user->can_gatekeep_trip_tickets ? 'checked' : '' }}>
                                <label class="form-check-label" for="can_gatekeep_trip_tickets">Gatekeeper</label>
                            </div>
                            <small class="text-muted d-block mt-2">All users can request trip tickets. These options add encoder, approver, manager, or gatekeeper access.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="javascript;" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- update password Modal -->
<div class="modal fade" id="updatePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fadeIn animated bx bx-user-plus"></i> Update Password </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="updatePasswordForm">
                @csrf
                <input type="hidden" id="user_id" value="{{ $user->id }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="password" class="form-label">Your Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="upassword" class="form-label">User New Password</label>
                        <input type="password" class="form-control" id="upassword" name="upassword" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <a href="javascript;" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>


@section('js-custom')
    <script src="{{ asset('assets/plugins/validation/jquery.validate.min.js') }}"></script>
	<script src="{{ asset('assets/plugins/validation/validation-script.js') }}"></script>
    <script>
        const ADMIN_ROUTE_PLACEHOLDER = "__ADMIN_PARAM__";
        const ADMIN_GET_SECTIONS_URL = "{{ url('/administrative/get-sections') }}";
        const ADMIN_SAVE_USER_URL = "{{ route('administrative.user.save') }}";
        const ADMIN_EDIT_USER_URL = "{{ route('administrative.user.edit', ['user' => '__ADMIN_PARAM__']) }}";
        const ADMIN_UPDATE_USER_URL = "{{ route('administrative.user.update', ['user' => '__ADMIN_PARAM__']) }}";
        const ADMIN_INDEX_URL = "{{ route('administrative.index') }}";
        const ADMIN_UPDATE_PASSWORD_URL = "{{ route('administrative.user.update-password', ['user' => '__ADMIN_PARAM__']) }}";
    </script>
    <script src="{{ asset('assets/js/custom/administrative.js') }}?v={{ filemtime(public_path('assets/js/custom/administrative.js')) }}"></script>

@endsection
@endsection
