<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <div class="logo-header" data-background-color="dark">
            <a href="{{ url('/') }}" class="logo">
                <img src="{{ asset('assets/img/kaiadmin/logo_light.svg') }}" alt="navbar brand" class="navbar-brand" height="20" />
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <x-lucide-house class="me-2" style="width: 16px; height: 16px;" />
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item {{ request()->routeIs('account.*') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#account">
                        <x-lucide-shield class="me-2" style="width: 16px; height: 16px;" />
                        <p>Account</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('account.*') ? 'show' : '' }}" id="account">
                        <ul class="nav nav-collapse">
                            <li class="{{ request()->routeIs('account.profile') ? 'active' : '' }}">
                                <a href="{{ route('account.profile') }}">
                                    <span class="sub-item">Profile</span>
                                </a>
                            </li>
                            @if(auth()->user()->hasGroupPermission('user'))
                            <li class="{{ request()->routeIs('account.users') ? 'active' : '' }}">
                                <a href="{{ route('account.users') }}">
                                    <span class="sub-item">Users</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasGroupPermission('role'))
                            <li class="{{ request()->routeIs('account.roles') ? 'active' : '' }}">
                                <a href="{{ route('account.roles') }}">
                                    <span class="sub-item">Roles</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasGroupPermission('permission'))
                            <li class="{{ request()->routeIs('account.permissions') ? 'active' : '' }}">
                                <a href="{{ route('account.permissions') }}">
                                    <span class="sub-item">Permissions</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                <li class="nav-item {{ request()->routeIs('master.*') ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#master">
                        <x-lucide-database class="me-2" style="width: 16px; height: 16px;" />
                        <p>Master Data</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse {{ request()->routeIs('master.*') ? 'show' : '' }}" id="master">
                        <ul class="nav nav-collapse">
                            @if(auth()->user()->hasGroupPermission('bank'))
                            <li class="{{ request()->routeIs('master.banks') ? 'active' : '' }}">
                                <a href="{{ route('master.banks') }}">
                                    <span class="sub-item">Banks</span>
                                </a>
                            </li>
                            @endif
                            @if(auth()->user()->hasGroupPermission('worker'))
                            <li class="{{ request()->routeIs('master.workers') ? 'active' : '' }}">
                                <a href="{{ route('master.workers') }}">
                                    <span class="sub-item">Workers</span>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal">
                        <x-lucide-log-out class="me-2" style="width: 16px; height: 16px;" />
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
