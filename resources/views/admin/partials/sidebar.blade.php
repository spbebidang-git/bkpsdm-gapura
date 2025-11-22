<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('admin.dashboard') }}" class="app-brand-link w-100 d-flex justify-content-center">
            <span class="app-brand-logo demo">
                <img src="{{ get_setting_meta('site_logo') ? asset('assets/media/images/managements/settings/' . get_setting_meta('site_logo')) : asset('assets/media/images/static/site_logo.png') }}"
                    style="height: 50px" alt="site_logo">
            </span>
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
                <div>Dashboard</div>
            </a>
        </li>

        <!-- User -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Pengguna</span>
        </li>

        <!-- Peserta -->
        <li class="menu-item {{ request()->routeIs('admin.participant*') ? 'active' : '' }}">
            <a href="{{ route('admin.participant.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Peserta</div>
            </a>
        </li>

        <!-- Instruktur -->
        <li class="menu-item {{ request()->routeIs('admin.instructor.*') ? 'active' : '' }}">
            <a href="{{ route('admin.instructor.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Instruktur</div>
            </a>
        </li>

        <!-- Course -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Kursus</span>
        </li>

        <!-- Kategori -->
        <li class="menu-item {{ request()->routeIs('admin.category.*') ? 'active' : '' }}">
            <a href="{{ route('admin.category.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Kategori</div>
            </a>
        </li>

        <!-- Kursus -->
        <li class="menu-item {{ request()->routeIs('admin.course.*') ? 'active' : '' }}">
            <a href="{{ route('admin.course.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Kursus</div>
            </a>
        </li>

        <!-- Ulasan -->
        <li class="menu-item {{ request()->routeIs('admin.review.*') ? 'active' : '' }}">
            <a href="{{ route('admin.review.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Ulasan</div>
            </a>
        </li>

        <!-- Sertifikat -->
        <li class="menu-item {{ request()->routeIs('admin.certificate.*') ? 'active' : '' }}">
            <a href="{{ route('admin.certificate.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Sertifikat</div>
            </a>
        </li>

        <!-- Hasil -->
        <li class="menu-item {{ request()->routeIs('admin.result.*') ? 'active' : '' }}">
            <a href="{{ route('admin.result.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Hasil</div>
            </a>
        </li>

        <!-- Performa Kursus -->
        <li class="menu-item {{ request()->routeIs('admin.course-performance.*') ? 'active' : '' }}">
            <a href="{{ route('admin.course-performance.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Performa Kursus</div>
            </a>
        </li>

        <!-- Others -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Lainnya</span>
        </li>

        <!-- Bantuan -->
        <li class="menu-item {{ request()->routeIs('admin.help.*') ? 'active' : '' }}">
            <a href="{{ route('admin.help.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div>Bantuan</div>
            </a>
        </li>

        <!-- Management -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Manajemen</span>
        </li>

        <!-- Peran -->
        <li class="menu-item {{ request()->routeIs('admin.management.role.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.role.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div>Peran</div>
            </a>
        </li>

        <!-- Hak Akses -->
        <li class="menu-item {{ request()->routeIs('admin.management.permission.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.permission.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div>Hak Akses</div>
            </a>
        </li>

        <!-- Pengguna -->
        <li class="menu-item {{ request()->routeIs('admin.management.user.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.user.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div>Pengguna</div>
            </a>
        </li>

        <!-- Pengaturan -->
        <li class="menu-item {{ request()->routeIs('admin.management.setting.*') ? 'active' : '' }}">
            <a href="{{ route('admin.management.setting.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div>Pengaturan</div>
            </a>
        </li>

    </ul>
</aside>
