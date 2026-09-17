@extends('layouts.admin')

@section('title', 'Create FAQ')
@section('page_title', 'Create FAQ Article')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}">FAQs</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">FAQ Question &amp; Answer</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.faqs.store') }}" method="POST">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Audience Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General Questions</option>
                                <option value="customers" {{ old('category') === 'customers' ? 'selected' : '' }}>For Customers / Homeowners</option>
                                <option value="contractors" {{ old('category') === 'contractors' ? 'selected' : '' }}>For Contractors / Businesses</option>
                                <option value="payments" {{ old('category') === 'payments' ? 'selected' : '' }}>Payments &amp; Invoices</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Display Order <span class="text-danger">*</span></label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 1) }}" required />
                        </div>
                        <div class="col-md-3 d-flex align-items-center mt-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_published" id="is_published" value="1" {{ old('is_published', '1') ? 'checked' : '' }} />
                                <label class="form-check-label fw-semibold" for="is_published">Published</label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Question <span class="text-danger">*</span></label>
                            <input type="text" name="question" class="form-control" placeholder="e.g. How do I request a quote from a contractor?" value="{{ old('question') }}" required />
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Answer <span class="text-danger">*</span></label>
                            <textarea name="answer" rows="5" class="form-control" placeholder="Comprehensive explanation..." required>{{ old('answer') }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.faqs.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-plus me-1"></i> Save FAQ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

