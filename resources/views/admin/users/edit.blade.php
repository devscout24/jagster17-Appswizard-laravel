@extends('layouts.admin')

@section('title', 'Edit Administrator - ' . $admin->name)
@section('page_title', 'Edit Administrator Account')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Admin Users</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Administrator: {{ $admin->name }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.update', $admin->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $admin->name) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $admin->email) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            @php
                                $currentRole = $admin->roles->pluck('name')->first() ?? 'admin';
                            @endphp
                            <select name="role" class="form-select" required>
                                <option value="admin" {{ old('role', $currentRole) === 'admin' ? 'selected' : '' }}>Administrator (Standard)</option>
                                <option value="super_admin" {{ old('role', $currentRole) === 'super_admin' ? 'selected' : '' }}>Super Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', $admin->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $admin->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••" />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

