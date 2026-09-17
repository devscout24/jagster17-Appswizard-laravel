@extends('layouts.admin')

@section('title', 'Create Administrator')
@section('page_title', 'Create Administrator Account')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Admin Users</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">New Administrator Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.users.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator (Standard)</option>
                                <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-user-plus me-1"></i> Create Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

