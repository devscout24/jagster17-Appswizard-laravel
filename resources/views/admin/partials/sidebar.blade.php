<div class="sidenav-menu">
    <!-- Brand Logo -->
    <a href="{{ route('admin.dashboard') }}" class="logo">
        <span class="logo logo-light">
            <span class="logo-lg"><img src="{{ asset('logo.png') }}" class="sidebar-brand-logo" alt="ValorHub" height="34" /></span>
            <span class="logo-sm"><img src="{{ asset('logo.png') }}" class="sidebar-brand-logo" alt="VH" height="24" /></span>
        </span>

        <span class="logo logo-dark">
            <span class="logo-lg"><img src="{{ asset('logo.png') }}" class="sidebar-brand-logo" alt="ValorHub" height="34" /></span>
            <span class="logo-sm"><img src="{{ asset('logo.png') }}" class="sidebar-brand-logo" alt="VH" height="24" /></span>
        </span>
    </a>

    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-on-hover">
        <i class="ti ti-circle align-middle"></i>
    </button>

    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-offcanvas">
        <i class="ti ti-menu-4 align-middle"></i>
    </button>

    <div class="scrollbar" data-simplebar="">
        <div id="user-profile-settings" class="sidenav-user" style="background: url({{ asset('assets/images/user-bg-pattern.svg') }})">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="{{ route('admin.profile.edit') }}" class="link-reset">
                        <img src="{{ asset(Auth::user()->avatar ?? 'assets/images/users/user-1.jpg') }}" alt="user-image" class="rounded-circle mb-2 avatar-md" />
                        <span class="sidenav-user-name fw-bold d-block">{{ Auth::user()->name ?? 'Administrator' }}</span>
                        <span class="fs-12 fw-semibold text-muted text-uppercase">{{ Auth::user()->roles->first()?->name ?? 'Admin' }}</span>
                    </a>
                </div>
                <div>
                    <a class="dropdown-toggle drop-arrow-none link-reset sidenav-user-set-icon" data-bs-toggle="dropdown" data-bs-offset="0,12" href="#!" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-settings fs-20 align-middle ms-1"></i>
                    </a>
                    <div class="dropdown-menu">
                        <a href="{{ route('admin.profile.edit') }}" class="dropdown-item">
                            <i class="ti ti-user-circle me-1 fs-lg align-middle"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger fw-semibold">
                                <i class="ti ti-logout me-1 fs-lg align-middle"></i>
                                <span class="align-middle">Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!--- Sidenav Menu -->
        <div id="sidenav-menu">
            <ul class="side-nav">
                <li class="side-nav-title mt-2">Main</li>

                <!-- Dashboard -->
                <li class="side-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-dashboard"></i></span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>

                <li class="side-nav-title mt-2">Platform Management</li>

                <!-- Contractors -->
                <li class="side-nav-item {{ request()->routeIs('admin.contractors.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.contractors.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-tool"></i></span>
                        <span class="menu-text">Contractors</span>
                    </a>
                </li>

                <!-- Customers -->
                <li class="side-nav-item {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.customers.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-users"></i></span>
                        <span class="menu-text">Customers</span>
                    </a>
                </li>

                <!-- Service Categories -->
                <li class="side-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.categories.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-category-2"></i></span>
                        <span class="menu-text">Categories</span>
                    </a>
                </li>

                <!-- Contractor Services -->
                <li class="side-nav-item {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.services.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-briefcase"></i></span>
                        <span class="menu-text">Services</span>
                    </a>
                </li>

                <!-- Marketplace Products -->
                <li class="side-nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.products.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-shopping-bag"></i></span>
                        <span class="menu-text">Marketplace</span>
                    </a>
                </li>

                <li class="side-nav-title mt-2">Leads & Operations</li>

                <!-- Quote Requests -->
                <li class="side-nav-item {{ request()->routeIs('admin.quotes.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.quotes.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                        <span class="menu-text">Quotes & Leads</span>
                    </a>
                </li>

                <!-- Projects -->
                <li class="side-nav-item {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.projects.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-hammer"></i></span>
                        <span class="menu-text">Projects</span>
                    </a>
                </li>

                <!-- Invoices & Payments -->
                <li class="side-nav-item {{ request()->routeIs('admin.invoices.*', 'admin.payments.*') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#menuInvoices" aria-expanded="{{ request()->routeIs('admin.invoices.*', 'admin.payments.*') ? 'true' : 'false' }}" aria-controls="menuInvoices" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-receipt"></i></span>
                        <span class="menu-text">Invoices & Billing</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.invoices.*', 'admin.payments.*') ? 'show' : '' }}" id="menuInvoices">
                        <ul class="sub-menu">
                            <li class="side-nav-item {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.invoices.index') }}" class="side-nav-link">
                                    <span class="menu-text">Invoices</span>
                                </a>
                            </li>
                            <li class="side-nav-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.payments.index') }}" class="side-nav-link">
                                    <span class="menu-text">Customer Payments</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Subscriptions & Plans -->
                <li class="side-nav-item {{ request()->routeIs('admin.plans.*', 'admin.subscriptions.*', 'admin.billing-history.*') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#menuSubscriptions" aria-expanded="{{ request()->routeIs('admin.plans.*', 'admin.subscriptions.*', 'admin.billing-history.*') ? 'true' : 'false' }}" aria-controls="menuSubscriptions" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-crown"></i></span>
                        <span class="menu-text">Subscriptions</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('admin.plans.*', 'admin.subscriptions.*', 'admin.billing-history.*') ? 'show' : '' }}" id="menuSubscriptions">
                        <ul class="sub-menu">
                            <li class="side-nav-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.plans.index') }}" class="side-nav-link">
                                    <span class="menu-text">Membership Plans</span>
                                </a>
                            </li>
                            <li class="side-nav-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.subscriptions.index') }}" class="side-nav-link">
                                    <span class="menu-text">Active Subscriptions</span>
                                </a>
                            </li>
                            <li class="side-nav-item {{ request()->routeIs('admin.billing-history.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.billing-history.index') }}" class="side-nav-link">
                                    <span class="menu-text">Billing History</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="side-nav-title mt-2">Community & Support</li>

                <!-- Reviews -->
                <li class="side-nav-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.reviews.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-star"></i></span>
                        <span class="menu-text">Reviews & Ratings</span>
                    </a>
                </li>

                <!-- Veteran Nominations -->
                <li class="side-nav-item {{ request()->routeIs('admin.veteran-nominations.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.veteran-nominations.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-medal"></i></span>
                        <span class="menu-text">Giving Back Grants</span>
                    </a>
                </li>

                <!-- Support Messages -->
                <li class="side-nav-item {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.contact-messages.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-messages"></i></span>
                        <span class="menu-text">Support Inquiries</span>
                    </a>
                </li>

                <!-- FAQs -->
                <li class="side-nav-item {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.faqs.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-help-circle"></i></span>
                        <span class="menu-text">FAQs</span>
                    </a>
                </li>

                <li class="side-nav-title mt-2">System</li>

                <!-- Admin Users -->
                <li class="side-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-shield-lock"></i></span>
                        <span class="menu-text">Admin Accounts</span>
                    </a>
                </li>

                <!-- Profile Settings -->
                <li class="side-nav-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.profile.edit') }}" class="side-nav-link">
                        <span class="menu-icon"><i class="ti ti-settings"></i></span>
                        <span class="menu-text">Profile Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

