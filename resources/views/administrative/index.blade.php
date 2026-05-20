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
                <li class="breadcrumb-item active" aria-current="page">List</li>
            </ol>
        </nav>
    </div>
</div>
<!--end breadcrumb-->

<div class="row">
    <div class="col-12 col-lg-6 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">User List</h6>
                    </div>
                    <div class="dropdown ms-auto">
                        <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class='bx bx-plus-circle font-22 text-option'></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 user-table">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th style="width: 50px;">Name</th>
                                <th>Department</th>
                                <th style="width: 100px !important;">Section</th>

                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('administrative.user.edit', ['user' => $user->id]) }}" class="btn btn-primary btn-sm">View</a>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm delete-user-button"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->full_name }}">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                                <td>{{ $user->full_name }}</td>
                                <td>{{ $user->department->name ?? 'N/A' }}</td>
                                <td style="width: 100px !important;">{{ $user->section->name ?? 'N/A' }}</td>

                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                   </div>
            </div>

        </div>
    </div>
    <div class="col-12 col-lg-6 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Issue Category</h6>
                    </div>
                    <div class="dropdown ms-auto">
                        <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class='bx bx-plus-circle font-22 text-option'></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name Action</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issues as $issue)
                            <tr>
                                <td>{{ $issue->id }}</td>
                                <td>{{ $issue->name }}</td>
                                <td>
                                    <a href="javascript:;" class="btn btn-secondary btn-sm">Action</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                   </div>
            </div>

        </div>
    </div>
</div><!--end row-->

<!-- Add New User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="text-muted">Optional. If blank, default password is <strong>123456</strong>.</small>
                    </div>
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department_id" required>
                            <option value="">SELECT A DEPARTMENT</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section_id" required>
                            <option value="">SELECT A SECTION</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ip_address" class="form-label">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" required>
                    </div>
                    <div class="mb-3">
                        <label for="user_type" class="form-label">User Type</label>
                        <select class="form-select" id="user_type" name="user_type" required>
                            <option value="User">User</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"></a><i class="bx bx-window-close"></i>Close</button>
                    <button type="submit" class="btn btn-primary"></a><i class="bx bx-user-plus"></i>Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- End Add New User Modal -->

<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="deleteUserForm">
            @csrf
            <input type="hidden" id="delete_user_id" name="user_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete <strong id="delete_user_name">this user</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="deleteUserSubmitButton">Delete User</button>
                </div>
            </div>
        </form>
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
        const ADMIN_DELETE_USER_URL = "{{ route('administrative.user.destroy', ['user' => '__ADMIN_PARAM__']) }}";
    </script>
    <script src="{{ asset('assets/js/custom/administrative.js') }}?v={{ filemtime(public_path('assets/js/custom/administrative.js')) }}"></script>

@endsection
@endsection
