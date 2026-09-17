<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo light -->
                <a href="{{ route('admin.dashboard') }}" class="logo-light">
                    <span class="logo-lg">
                        <img src="{{ asset('logo.png') }}" alt="ValorHub" height="32" />
                    </span>
                    <span class="logo-sm">
                        <img src="{{ asset('logo.png') }}" alt="VH" height="24" />
                    </span>
                </a>

                <!-- Logo Dark -->
                <a href="{{ route('admin.dashboard') }}" class="logo-dark">
                    <span class="logo-lg">
                        <img src="{{ asset('logo.png') }}" alt="ValorHub" height="32" />
                    </span>
                    <span class="logo-sm">
                        <img src="{{ asset('logo.png') }}" alt="VH" height="24" />
                    </span>
                </a>
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button class="sidenav-toggle-button btn btn-primary btn-icon">
                <i class="ti ti-menu-4"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="topnav-toggle-button px-2" data-bs-toggle="collapse" data-bs-target="#topnav-menu">
                <i class="ti ti-menu-4"></i>
            </button>

            <div id="search-box-rounded" class="app-search d-none d-xl-flex">
                <form action="{{ route('admin.contractors.index') }}" method="GET">
                    <input type="search" class="form-control rounded-pill topbar-search" name="search" placeholder="Search contractors, quotes, projects..." />
                    <i class="ti ti-search app-search-icon text-muted"></i>
                </form>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Theme Mode Dropdown -->
            <div id="theme-dropdown" class="topbar-item d-none d-sm-flex">
                <div class="dropdown">
                    <button class="topbar-link" data-bs-toggle="dropdown" type="button" aria-haspopup="false" aria-expanded="false" title="Theme Mode">
                        <i class="ti ti-sun topbar-link-icon d-none" id="theme-icon-light"></i>
                        <i class="ti ti-moon topbar-link-icon d-none" id="theme-icon-dark"></i>
                        <i class="ti ti-sun-moon topbar-link-icon" id="theme-icon-system"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" data-thememode="dropdown">
                        <label class="dropdown-item cursor-pointer">
                            <input class="form-check-input" type="radio" name="data-bs-theme" value="light" style="display: none" />
                            <i class="ti ti-sun align-middle me-1 fs-16"></i>
                            <span class="align-middle">Light</span>
                        </label>
                        <label class="dropdown-item cursor-pointer">
                            <input class="form-check-input" type="radio" name="data-bs-theme" value="dark" style="display: none" />
                            <i class="ti ti-moon align-middle me-1 fs-16"></i>
                            <span class="align-middle">Dark</span>
                        </label>
                        <label class="dropdown-item cursor-pointer">
                            <input class="form-check-input" type="radio" name="data-bs-theme" value="system" style="display: none" />
                            <i class="ti ti-sun-moon align-middle me-1 fs-16"></i>
                            <span class="align-middle">System</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Fullscreen Toggle -->
            <div id="fullscreen-toggler" class="topbar-item d-none d-md-flex">
                <button class="topbar-link" type="button" data-toggle="fullscreen" title="Toggle Fullscreen">
                    <i class="ti ti-maximize topbar-link-icon"></i>
                    <i class="ti ti-minimize topbar-link-icon d-none"></i>
                </button>
            </div>

            <!-- User Dropdown -->
            <div class="topbar-item">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle drop-arrow-none px-2 d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="{{ asset(Auth::user()->avatar ?? 'assets/images/users/user-1.jpg') }}" class="avatar-sm rounded-circle" alt="{{ Auth::user()->name ?? 'Admin' }}" />
                        <div class="d-none d-lg-block text-start">
                            <span class="fw-semibold d-block fs-sm lh-1">{{ Auth::user()->name ?? 'Administrator' }}</span>
                            <span class="text-muted fs-xs">{{ Auth::user()->roles->first()?->name ?? 'Admin' }}</span>
                        </div>
                        <i class="ti ti-chevron-down d-none d-lg-inline-block ms-1 fs-xs"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Signed in as <span class="fw-bold">{{ Auth::user()->email ?? 'admin' }}</span></h6>
                        </div>

                        <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                            <i class="ti ti-user-circle me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Account Settings</span>
                        </a>

                        <a href="{{ route('admin.users.index') }}" class="dropdown-item">
                            <i class="ti ti-shield-lock me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Admin Users</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger fw-semibold">
                                <i class="ti ti-logout me-1 fs-lg align-middle"></i>
                                <span class="align-middle">Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

