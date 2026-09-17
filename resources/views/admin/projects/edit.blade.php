@extends('layouts.admin')

@section('title', 'Edit Project - ' . $project->title)
@section('page_title', 'Edit Project Progress')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.projects.index') }}">Projects</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.projects.show', $project->id) }}">{{ $project->title }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-bottom">
                <h5 class="card-title mb-0 fw-semibold">Edit Project Details &amp; Milestone</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.projects.update', $project->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Project Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $project->title) }}" required />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Total Contract Amount ($)</label>
                            <input type="number" step="0.01" name="total_amount" class="form-control" value="{{ old('total_amount', $project->total_amount) }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Progress Completion (0-100%) <span class="text-danger">*</span></label>
                            <input type="number" name="progress_percent" class="form-control" min="0" max="100" value="{{ old('progress_percent', $project->progress_percent) }}" required />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('Y-m-d') : '') }}" />
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estimated Due Date</label>
                            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $project->due_date ? \Carbon\Carbon::parse($project->due_date)->format('Y-m-d') : '') }}" />
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Project Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="in_progress" {{ old('status', $project->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="scheduled" {{ old('status', $project->status) === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="pending_materials" {{ old('status', $project->status) === 'pending_materials' ? 'selected' : '' }}>Pending Materials</option>
                                <option value="completed" {{ old('status', $project->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ old('status', $project->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Project Description / Scope Notes</label>
                            <textarea name="description" rows="4" class="form-control">{{ old('description', $project->description) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('admin.projects.show', $project->id) }}" class="btn btn-light">Cancel</a>
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

