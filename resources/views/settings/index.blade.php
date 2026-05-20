@extends('layout.app')

@section('content')
<div class="row">
    <div class="col-12 col-lg-6 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Update Password</h6>
                    </div>
                    <div class="dropdown ms-auto">
                        <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class='font-22 text-option'>&NonBreakingSpace;</i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form id="passwordForm">
                    @csrf
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" id="new_password" required>
                        <small class="form-text text-muted">
                            Must be at least 8 characters, include 1 uppercase letter, 1 number, and 1 special character.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

@section('js-custom')

    <script>
        const SETTING_PASSWORD_URL = "{{ route('settings.update-password') }}";
    </script>

    <script src="{{ asset('assets/js/custom/settings.js') }}"></script>
@endsection

@endsection
