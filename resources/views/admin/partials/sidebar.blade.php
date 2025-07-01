<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link  w-100 d-flex justify-content-center">
            <span class="app-brand-logo demo">
                <img src="{{ get_setting_meta('site_logo') ? asset('assets/media/images/managements/settings/' . get_setting_meta('site_logo')) : asset('assets/media/images/static/site_logo.png') }}"
                    style="height: 50px" alt="site_logo">
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <a href="{{ route('admin.dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">User</span>
        </li>
        {{-- Participant --}}
        <li class="menu-item {{ request()->routeIs('admin.participant*') ? 'active' : '' }}">
            <a href="{{ route('admin.participant.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Participant</div>
            </a>
        </li>
        {{-- Instructor --}}
        <li class="menu-item {{ request()->routeIs('admin.instructor.*') ? 'active' : '' }}">
            <a href="{{ route('admin.instructor.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Instructor</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Course</span>
        </li>
        {{-- Category --}}
        <li class="menu-item {{ request()->routeIs('admin.category.*') ? 'active' : '' }}">
            <a href="{{ route('admin.category.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Category</div>
            </a>
        </li>
        {{-- Course --}}
        <li class="menu-item {{ request()->routeIs('admin.course.*') ? 'active' : '' }}">
            <a href="{{ route('admin.course.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Course</div>
            </a>
        </li>
        {{-- Review --}}
        <li class="menu-item {{ request()->routeIs('admin.review.*') ? 'active' : '' }}">
            <a href="{{ route('admin.review.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Review</div>
            </a>
        </li>
        {{-- Certificate --}}
        <li class="menu-item {{ request()->routeIs('admin.certificate.*') ? 'active' : '' }}">
            <a href="{{ route('admin.certificate.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Certificate</div>
            </a>
        </li>

        {{-- Result --}}
        <li class="menu-item {{ request()->routeIs('admin.result.*') ? 'active' : '' }}">
            <a href="{{ route('admin.result.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Layouts">Result</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Management</span>
        </li>
        {{-- Role --}}
        <li class="menu-item {{ request()->routeIs('admin.management.role.*') ? 'active' : '' }} ">
            <a href="{{ route('admin.management.role.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div class="">Roles</div>
            </a>
        </li>
        {{-- Permission --}}
        <li class="menu-item {{ request()->routeIs('admin.management.permission.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.permission.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div class="">Permissions</div>
            </a>
        </li>
        {{-- Users --}}
        <li class="menu-item {{ request()->routeIs('admin.management.user.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.user.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div class="">Users</div>
            </a>
        </li>
        {{-- Settings --}}
        <li class="menu-item {{ request()->routeIs('admin.management.setting.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.setting.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div class="">Settings</div>
            </a>
        </li>
    </ul>
</aside>
