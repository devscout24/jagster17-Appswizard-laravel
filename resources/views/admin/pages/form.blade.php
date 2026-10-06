@extends('layouts.admin')

@section('title', $page->exists ? 'Edit Page' : 'New Page')
@section('page_title', $page->exists ? 'Edit: ' . $page->title : 'New Page')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}">Pages</a></li>
    <li class="breadcrumb-item active">{{ $page->exists ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ $page->exists ? route('admin.pages.update', $page) : route('admin.pages.store') }}" method="POST">
                    @csrf
                    @if ($page->exists)
                        @method('PUT')
                    @endif

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $page->title) }}" required />
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="slug">URL slug</label>
                            <div class="input-group">
                                <span class="input-group-text">/</span>
                                <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $page->slug) }}" placeholder="privacy-policy" />
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Leave blank to generate it from the title.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="meta_description">Meta description</label>
                            <input type="text" id="meta_description" name="meta_description" class="form-control" maxlength="255" value="{{ old('meta_description', $page->meta_description) }}" />
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="content">Content <span class="text-danger">*</span></label>
                            <textarea id="content" name="content" rows="18" class="form-control font-monospace @error('content') is-invalid @enderror" required>{{ old('content', $page->content) }}</textarea>
                            @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">HTML is supported (headings, paragraphs, lists, links).</div>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $page->is_published) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_published">Published on the website</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.pages.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="ti ti-device-floppy me-1"></i> Save Page</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
