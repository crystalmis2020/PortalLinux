@extends('layout.app')

@section('content')
<div class="row">
    <div class="col-12 col-lg-8">
        <div class="card radius-10">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img
                        src="{{ $user->profile_photo_url }}"
                        class="rounded-circle"
                        alt="profile photo"
                        width="90"
                        height="90"
                        style="object-fit: cover; cursor: pointer;"
                        data-bs-toggle="modal"
                        data-bs-target="#profilePhotoPreviewModal"
                    >
                    <div>
                        <h5 class="mb-1">{{ $user->full_name }}</h5>
                        <p class="mb-0 text-secondary">{{ '@' . $user->username }}</p>
                        <button
                            type="button"
                            class="btn btn-sm btn-primary mt-2"
                            onclick="document.getElementById('profile-photo-input-page').click();"
                        >
                            Change Profile Picture
                        </button>
                    </div>
                </div>

                <form id="profile-photo-form-page" action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data" style="display: none;">
                    @csrf
                    <input
                        type="file"
                        id="profile-photo-input-page"
                        name="profile_photo"
                        accept=".jpg,.jpeg,.png,.webp,image/*"
                        onchange="if (this.files.length) { document.getElementById('profile-photo-form-page').submit(); }"
                    >
                </form>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Full Name</label>
                        <input type="text" class="form-control" value="{{ $user->full_name }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Username</label>
                        <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Department</label>
                        <input type="text" class="form-control" value="{{ $user->department->name ?? 'N/A' }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Section</label>
                        <input type="text" class="form-control" value="{{ $user->section->name ?? 'N/A' }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary">User Type</label>
                        <input type="text" class="form-control text-capitalize" value="{{ $user->user_type }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-secondary">Last Login</label>
                        <input type="text" class="form-control" value="{{ $user->last_login ?? 'N/A' }}" disabled>
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
                    alt="profile photo preview"
                    class="img-fluid rounded"
                    style="max-height: 70vh; object-fit: contain;"
                >
            </div>
        </div>
    </div>
</div>
@endsection
