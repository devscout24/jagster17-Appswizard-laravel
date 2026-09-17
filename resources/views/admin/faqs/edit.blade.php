@extends('layouts.admin')

@section('title', 'Edit FAQ')
@section('page_title', 'Edit FAQ Article')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}">FAQs</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit FAQ Article</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Audience Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="general" {{ old('category', $faq->category) === 'general' ? 'selected' : '' }}>General Questions</option>
                                <option value="customers" {{ old('category', $faq->category) === 'customers' ? 'selected' : '' }}>For Customers / Homeowners</option>
                                <option value="contractors" {{ old('category', $faq->category) === 'contractors' ? 'selected' : '' }}>For Contractors / Businesses</option>
                                <option value="payments" {{ old('category', $faq->category) === 'payments' ? 'selected' : '' }}>Payments &amp; Invoices</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Display Order <span class="text-danger">*</span></label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $faq->sort_order) }}" required />
                        </div>
                        <div class="col-md-3 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', $faq->is_published) ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_published">Published</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Question <span class="text-danger">*</span></label>
                            <input type="text" name="question" class="form-control" value="{{ old('question', $faq->question) }}" required />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Answer <span class="text-danger">*</span></label>
                            <textarea name="answer" rows="5" class="form-control" required>{{ old('answer', $faq->answer) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.faqs.index') }}" class="btn btn-light">Cancel</a>
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

