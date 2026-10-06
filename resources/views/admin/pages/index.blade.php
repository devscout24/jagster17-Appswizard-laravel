@extends('layouts.admin')

@section('title', 'Website Pages')
@section('page_title', 'Website Pages')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Pages</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i> New Page</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Website URL</th>
                        <th>Status</th>
                        <th>Last updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td><a href="{{ route('admin.pages.edit', $page) }}" class="fw-bold text-primary">{{ $page->title }}</a></td>
                            <td><code>/{{ $page->slug }}</code></td>
                            <td>
                                <span class="badge {{ $page->is_published ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }} text-uppercase">
                                    {{ $page->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="fs-xs text-muted">{{ $page->updated_at->format('M d, Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-icon btn-light" title="Edit"><i class="ti ti-edit"></i></a>
                                <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Delete this page? The website link will stop working.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete"><i class="ti ti-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No pages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
