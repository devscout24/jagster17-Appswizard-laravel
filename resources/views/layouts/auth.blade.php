<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin Authentication') | ValorHub / JAGSTAR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="ValorHub Contractor & Client Admin Portal" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" />

    <!-- Theme Config Js -->
    <script src="{{ asset('assets/js/config.js') }}"></script>

    <!-- Vendor css -->
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Brand css -->
    <link href="{{ asset('assets/css/brand.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
</head>
<body>
    <div class="position-absolute top-0 end-0">
        <img src="{{ asset('assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" alt="auth-card-bg" />
    </div>
    <div class="position-absolute bottom-0 start-0" style="transform: rotate(180deg)">
        <img src="{{ asset('assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" alt="auth-card-bg" />
    </div>

    @yield('content')

    <!-- Vendor js -->
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>

