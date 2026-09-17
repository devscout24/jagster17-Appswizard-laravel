@extends('layouts.admin')

@section('title', 'Admin Profile Settings')
@section('page_title', 'My Profile & Account Settings')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Profile Settings</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Personal Information &amp; Security</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="d-flex align-items-center mb-4">
                        <img src="{{ asset($user->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-lg rounded-circle border p-1 me-3" alt="Avatar" />
                        <div>
                            <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
                            <span class="badge bg-primary text-uppercase">{{ $user->roles->first()?->name ?? 'Administrator' }}</span>
                        </div>
                    </div>

                    <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">Personal Profile</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" />
                        </div>
                    </div>

                    <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">Change Password</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="••••••••" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 8 characters" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" class="form-control" placeholder="••••••••" />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-device-floppy me-1"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

