@extends('layouts.admin')

@section('title', 'Site Settings')
@section('page_title', 'Site Settings')

@section('breadcrumbs')
    <li class="breadcrumb-item active">Site Settings</li>
@endsection

@php
    $titles = [
        'general' => ['General', 'ti-world'],
        'contact' => ['Contact details', 'ti-address-book'],
        'social' => ['Social media links', 'ti-brand-facebook'],
        'platform' => ['Platform & billing', 'ti-receipt'],
    ];
@endphp

@section('content')
<form action="{{ route('admin.settings.update') }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-3">
        @foreach ($groups as $group => $fields)
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="card-title mb-0 fw-semibold"><i class="ti {{ $titles[$group][1] ?? 'ti-settings' }} me-1"></i> {{ $titles[$group][0] ?? ucfirst($group) }}</h5>
                    </div>
                    <div class="card-body">
                        @foreach ($fields as $key => $field)
                            <div class="mb-3">
                                <label class="form-label" for="{{ $key }}">{{ $field['label'] }}</label>
                                <input type="{{ str_ends_with($key, '_url') ? 'url' : (str_ends_with($key, '_email') ? 'email' : (str_ends_with($key, '_percent') ? 'number' : 'text')) }}"
                                       id="{{ $key }}" name="{{ $key }}" step="any"
                                       class="form-control @error($key) is-invalid @enderror"
                                       value="{{ old($key, $values[$key] ?? '') }}" />
                                @error($key)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary px-4"><i class="ti ti-device-floppy me-1"></i> Save Settings</button>
    </div>
</form>
@endsection
