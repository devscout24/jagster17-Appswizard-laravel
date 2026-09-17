@extends('layouts.admin')

@section('title', 'Edit Invoice - ' . $invoice->invoice_number)
@section('page_title', 'Edit Invoice ' . $invoice->invoice_number)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.show', $invoice->id) }}">{{ $invoice->invoice_number }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Invoice Figures &amp; Payment Status</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.invoices.update', $invoice->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Total Invoice Amount ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount', $invoice->amount) }}" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Platform Fee ($)</label>
                            <input type="number" step="0.01" name="platform_fee" class="form-control" value="{{ old('platform_fee', $invoice->platform_fee) }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Labor Amount ($)</label>
                            <input type="number" step="0.01" name="labor_amount" class="form-control" value="{{ old('labor_amount', $invoice->labor_amount) }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Materials Amount ($)</label>
                            <input type="number" step="0.01" name="materials_amount" class="form-control" value="{{ old('materials_amount', $invoice->materials_amount) }}" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                            <input type="date" name="issued_at" class="form-control" value="{{ old('issued_at', $invoice->issued_at?->format('Y-m-d')) }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_at" class="form-control" value="{{ old('due_at', $invoice->due_at?->format('Y-m-d')) }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="paid" {{ old('status', $invoice->status) === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="pending" {{ old('status', $invoice->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="overdue" {{ old('status', $invoice->status) === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="draft" {{ old('status', $invoice->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Payment Date (if paid)</label>
                            <input type="date" name="paid_at" class="form-control" value="{{ old('paid_at', $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('Y-m-d') : '') }}" />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Invoice Notes</label>
                            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="btn btn-light">Cancel</a>
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

