<!DOCTYPE html>
<html lang="id" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path=""
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @hasSection('breadcrumb')
            @yield('breadcrumb') -
        @endif
        {{ get_setting_meta('site_title') ? get_setting_meta('site_title') : 'Site Title' }}
    </title>

    <meta name="description" content="" />

    <link rel="icon" type="image/x-icon" href="{{ asset('assets/admin/img/favicon/favicon.ico') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/css/core.css') }}"
        class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/css/theme-default.css') }}"
        class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('assets/admin/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

    <script src="{{ asset('assets/admin/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('assets/admin/js/config.js') }}"></script>

    @stack('styles')

    <style>
        .row {
            margin-block: 0.5rem !important;
        }

        #table {
            width: 100% !important
        }

        .swal2-container {
            z-index: 9999;
        }
    </style>

    @vite(['resources/js/admin.js'])
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            {{-- SIDEBAR --}}
            @include('admin.partials.sidebar')

            <div class="layout-page">
                {{-- NAVBAR --}}
                @include('admin.partials.navbar')
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        @include('admin.partials.alert')
                        @yield('content')
                    </div>
                    <div class="content-backdrop fade"></div>
                </div>
            </div>
        </div>

        <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    <script src="{{ asset('assets/admin/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('assets/admin/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('assets/admin/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('assets/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script nonce="{{ Vite::cspNonce() }}" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script nonce="{{ Vite::cspNonce() }}" src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('assets/admin/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('assets/admin/js/main.js') }}"></script>

    @stack('scripts')
</body>

</html>
