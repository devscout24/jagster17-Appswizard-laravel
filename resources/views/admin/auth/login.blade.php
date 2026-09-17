@extends('layouts.auth')

@section('title', 'Admin Sign In')

@section('content')
<div class="auth-box overflow-hidden align-items-center d-flex">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-4 col-md-6 col-sm-8">
                <div class="card p-4 shadow-sm border-0">
                    <div class="auth-brand text-center mb-3">
                        <a href="{{ route('admin.login') }}">
                            <img src="{{ asset('logo.png') }}" alt="ValorHub Logo" height="46" class="img-fluid mb-2" />
                        </a>
                        <h4 class="fw-bold text-dark mt-2">Admin Portal Sign In</h4>
                        <p class="text-muted fs-sm">Enter your administrator credentials to access the management dashboard.</p>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="ti ti-info-circle me-1"></i> {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.login.submit') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="adminEmail" class="form-label">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-mail"></i></span>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="adminEmail" placeholder="admin@valorhub.com" value="{{ old('email', 'admin@valorhub.com') }}" required autofocus />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="adminPassword" placeholder="••••••••" value="password" required />
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" {{ old('remember') ? 'checked' : '' }} />
                                <label class="form-check-label fs-sm" for="rememberMe">Remember my session</label>
                            </div>
                        </div>

                        <div class="d-grid mb-2">
                            <button type="submit" class="btn btn-primary fw-semibold py-2">
                                <i class="ti ti-login me-1"></i> Sign In to Admin
                            </button>
                        </div>
                    </form>

                    <div class="bg-light p-3 rounded mt-3 text-center border">
                        <small class="text-muted d-block fw-semibold mb-1">Default Demo Admin Credentials:</small>
                        <small class="text-dark d-block">Email: <code>admin@valorhub.com</code></small>
                        <small class="text-dark d-block">Password: <code>password</code></small>
                    </div>
                </div>

                <p class="text-center text-muted mt-3 mb-0 fs-xs">
                    &copy; {{ date('Y') }} ValorHub Platform. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

