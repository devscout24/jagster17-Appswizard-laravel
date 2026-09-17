@extends('layouts.admin')

@section('title', 'Administrator Accounts')
@section('page_title', 'Admin Accounts & Roles')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Admin Users</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-user-plus me-1"></i> Add Administrator
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <h5 class="card-title mb-0 fw-semibold">Platform Staff &amp; Administrators</h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Administrator</th>
                        <th>Email</th>
                        <th>Assigned Roles</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admins as $admin)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset($admin->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-sm rounded-circle me-2" alt="Avatar" />
                                    <div>
                                        <strong class="text-dark d-block">{{ $admin->name }}</strong>
                                        @if($admin->id === Auth::id())
                                            <span class="badge bg-info-subtle text-info fs-xs">Current Session</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $admin->email }}</td>
                            <td>
                                @foreach($admin->roles as $role)
                                    <span class="badge {{ $role->name === 'super_admin' ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }} text-uppercase">
                                        {{ str_replace('_', ' ', $role->name) }}
                                    </span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge {{ $admin->status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                    {{ $admin->status ?? 'active' }}
                                </span>
                            </td>
                            <td class="fs-xs text-muted">
                                {{ $admin->created_at?->format('M d, Y') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $admin->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit Admin">
                                    <i class="ti ti-edit"></i>
                                </a>
                                @if($admin->id !== Auth::id())
                                    <form action="{{ route('admin.users.destroy', $admin->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this admin account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete Admin">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No administrator accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

