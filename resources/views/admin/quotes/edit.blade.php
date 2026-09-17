@extends('layouts.admin')

@section('title', 'Edit Quote Request ' . $quote->reference_number)
@section('page_title', 'Edit Quote Request')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.quotes.index') }}">Quotes</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.quotes.show', $quote->id) }}">{{ $quote->reference_number }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Quote Request: {{ $quote->reference_number }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.quotes.update', $quote->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Assigned Contractor</label>
                            <select name="business_id" class="form-select">
                                <option value="">-- Open Lead (Unassigned) --</option>
                                @foreach($contractors as $c)
                                    <option value="{{ $c->id }}" {{ old('business_id', $quote->business_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->businessProfile?->business_name ?? $c->name }} ({{ $c->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Service Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">-- Uncategorized --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $quote->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Project Title <span class="text-danger">*</span></label>
                            <input type="text" name="project_title" class="form-control" value="{{ old('project_title', $quote->project_title) }}" required />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quote Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="new" {{ old('status', $quote->status) === 'new' ? 'selected' : '' }}>New</option>
                                <option value="pending" {{ old('status', $quote->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="quoted" {{ old('status', $quote->status) === 'quoted' ? 'selected' : '' }}>Quoted</option>
                                <option value="accepted" {{ old('status', $quote->status) === 'accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="declined" {{ old('status', $quote->status) === 'declined' ? 'selected' : '' }}>Declined</option>
                                <option value="rejected" {{ old('status', $quote->status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="expired" {{ old('status', $quote->status) === 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Timeline</label>
                            <select name="timeline" class="form-select">
                                <option value="asap" {{ old('timeline', $quote->timeline) === 'asap' ? 'selected' : '' }}>ASAP</option>
                                <option value="within_1_week" {{ old('timeline', $quote->timeline) === 'within_1_week' ? 'selected' : '' }}>Within 1 Week</option>
                                <option value="within_1_month" {{ old('timeline', $quote->timeline) === 'within_1_month' ? 'selected' : '' }}>Within 1 Month</option>
                                <option value="flexible" {{ old('timeline', $quote->timeline) === 'flexible' ? 'selected' : '' }}>Flexible</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Project Scope &amp; Details</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description', $quote->description) }}</textarea>
                        </div>
                    </div>

                    <!-- Financial Breakdown Controls -->
                    <h6 class="text-primary text-uppercase fs-xs fw-bold mb-3 border-top pt-3">Contractor Quote Figures</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Total Quote Amount ($)</label>
                            <input type="number" step="0.01" name="quote_amount" class="form-control" value="{{ old('quote_amount', $quote->quote_amount) }}" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Labor Cost ($)</label>
                            <input type="number" step="0.01" name="labor_cost" class="form-control" value="{{ old('labor_cost', $quote->labor_cost) }}" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Materials Cost ($)</label>
                            <input type="number" step="0.01" name="materials_cost" class="form-control" value="{{ old('materials_cost', $quote->materials_cost) }}" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tax / Fees ($)</label>
                            <input type="number" step="0.01" name="tax_cost" class="form-control" value="{{ old('tax_cost', $quote->tax_cost) }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estimated Duration</label>
                            <input type="text" name="estimated_duration" class="form-control" placeholder="e.g. 3 Days" value="{{ old('estimated_duration', $quote->estimated_duration) }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contractor Notes</label>
                            <input type="text" name="contractor_notes" class="form-control" value="{{ old('contractor_notes', $quote->contractor_notes) }}" />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.quotes.show', $quote->id) }}" class="btn btn-light">Cancel</a>
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

