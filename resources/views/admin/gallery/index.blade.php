@extends('layouts.admin')

@section('title', 'Gallery Moderation')
@section('page_title', 'Contractor Gallery Moderation')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Gallery</li>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <form action="{{ route('admin.gallery.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <select name="business_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All contractors</option>
                    @foreach ($contractors as $contractor)
                        <option value="{{ $contractor->id }}" {{ (string) $businessId === (string) $contractor->id ? 'selected' : '' }}>
                            {{ $contractor->businessProfile?->business_name ?? $contractor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Visible &amp; hidden</option>
                    <option value="published" {{ $status === 'published' ? 'selected' : '' }}>Visible only</option>
                    <option value="hidden" {{ $status === 'hidden' ? 'selected' : '' }}>Hidden only</option>
                </select>
            </div>
            <div class="col-md-4 text-end fs-xs text-muted">{{ $photos->total() }} photos</div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse ($photos as $photo)
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card border-0 shadow-sm h-100 {{ $photo->status === 'hidden' ? 'opacity-50' : '' }}">
                <a href="{{ $photo->image_url }}" target="_blank" rel="noopener">
                    <img src="{{ $photo->image_url }}" alt="{{ $photo->caption }}" class="card-img-top object-fit-cover" style="height: 150px;" />
                </a>
                <div class="card-body p-2">
                    <div class="fw-semibold text-truncate fs-sm">{{ $photo->caption ?? 'Untitled' }}</div>
                    <div class="fs-xs text-muted text-truncate">
                        {{ $photo->business?->businessProfile?->business_name ?? $photo->business?->name }} ·
                        {{ \App\Models\GalleryPhoto::CATEGORIES[$photo->category] ?? $photo->category }}
                    </div>
                    <div class="d-flex gap-1 mt-2">
                        <form action="{{ route('admin.gallery.toggle', $photo) }}" method="POST" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light w-100">
                                <i class="ti {{ $photo->status === 'published' ? 'ti-eye-off' : 'ti-eye' }} me-1"></i>
                                {{ $photo->status === 'published' ? 'Hide' : 'Show' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.gallery.destroy', $photo) }}" method="POST" onsubmit="return confirm('Delete this photo permanently?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete"><i class="ti ti-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm"><div class="card-body text-center py-5 text-muted">
                <i class="ti ti-photo fs-2 d-block mb-2"></i>No gallery photos yet.
            </div></div>
        </div>
    @endforelse
</div>

@if ($photos->hasPages())
    <div class="mt-3">{{ $photos->links() }}</div>
@endif
@endsection
