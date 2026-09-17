<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin Dashboard') | ValorHub / JAGSTAR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="ValorHub Contractor & Client Admin Portal" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

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
    <!-- Begin page wrapper -->
    <div class="wrapper">
        <!-- Topbar -->
        @include('admin.partials.topbar')

        <!-- Sidenav -->
        @include('admin.partials.sidebar')

        <!-- Page Content -->
        <div class="content-page">
            <div class="container-fluid">
                <!-- Page Title / Breadcrumbs -->
                <div class="page-title-head d-flex align-items-center justify-content-between mb-3 mt-2">
                    <div>
                        <h4 class="page-main-title mb-0 fw-semibold">@yield('page_title', 'Dashboard')</h4>
                        <ol class="breadcrumb m-0 mt-1 fs-sm">
                            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                            @yield('breadcrumbs')
                        </ol>
                    </div>
                    <div>
                        @yield('page_actions')
                    </div>
                </div>

                <!-- Flash Alerts -->
                @include('admin.partials.alerts')

                <!-- Main View Content -->
                @yield('content')
            </div>

            <!-- Footer -->
            @include('admin.partials.footer')
        </div>
    </div>

    <!-- Vendor js -->
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <!-- ApexCharts -->
    <script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>

    @stack('scripts')
</body>
</html>

