@extends('layouts.admin')

@section('title', 'Knowledge Base FAQs')
@section('page_title', 'Frequently Asked Questions (FAQs)')

@section('breadcrumbs')
    <li class="breadcrumb-item active">FAQs</li>
@endsection

@section('page_actions')
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-sm btn-primary">
        <i class="ti ti-plus me-1"></i> Add FAQ
    </a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-bottom py-3">
        <form action="{{ route('admin.faqs.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="search" name="search" class="form-control" placeholder="Search question or answer..." value="{{ $search }}" />
                </div>
            </div>

            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all">All Audience Categories</option>
                    <option value="customers" {{ $category === 'customers' ? 'selected' : '' }}>For Customers</option>
                    <option value="contractors" {{ $category === 'contractors' ? 'selected' : '' }}>For Contractors</option>
                    <option value="payments" {{ $category === 'payments' ? 'selected' : '' }}>Payments &amp; Billing</option>
                    <option value="general" {{ $category === 'general' ? 'selected' : '' }}>General Questions</option>
                </select>
            </div>

            <div class="col-md-4 text-end">
                <a href="{{ route('admin.faqs.index') }}" class="btn btn-sm btn-light" title="Reset Filters">
                    <i class="ti ti-refresh me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Order</th>
                        <th>Question</th>
                        <th>Audience Category</th>
                        <th>Answer Summary</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($faqs as $faq)
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border">#{{ $faq->sort_order }}</span>
                            </td>
                            <td>
                                <strong class="text-dark d-block">{{ $faq->question }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary text-capitalize">
                                    {{ $faq->category }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted d-inline-block text-truncate" style="max-width: 320px;">
                                    {{ $faq->answer }}
                                </span>
                            </td>
                            <td>
                                @if($faq->is_published)
                                    <span class="badge bg-success-subtle text-success">Published</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="btn btn-sm btn-icon btn-light" title="Edit FAQ">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form action="{{ route('admin.faqs.destroy', $faq->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this FAQ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Delete FAQ">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No FAQ articles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($faqs->hasPages())
        <div class="card-footer bg-transparent border-top py-3">
            {{ $faqs->links() }}
        </div>
    @endif
</div>
@endsection

