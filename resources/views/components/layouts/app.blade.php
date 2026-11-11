@props([
    'title' => 'LinyTix Admin',
    'brandName' => 'LinyTix',
    'brandIcon' => 'fas fa-layer-group'
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>

    <link rel="stylesheet" href="{{ asset('assets/fontawesome-free-7.2.0-web/css/all.min.css')}}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('assets/bootstrap/css/bootstrap.min.css')}}">

    <link rel="stylesheet" href="{{ asset('assets/css/customize-theme.css')}}">
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

    @stack('styles')
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: 'Archivo', sans-serif;
            font-weight: 700;
            letter-spacing: -.02em;
        }

        h1, .h1 {
            font-weight: 800;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 1.5rem 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--bs-primary);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand i {
            font-size: 1.8rem;
        }

        .sidebar-menu {
            padding: 1.5rem 0;
        }

        .menu-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s;
            margin: 0.15rem 0.75rem;
            border-radius: 0;
            font-weight: 500;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background: var(--hover-bg);
            color: var(--bs-primary);
        }

        .sidebar-menu a.active {
            background: var(--accent-soft);
            color: var(--bs-primary);
            border-left-color: var(--bs-primary);
            font-weight: 600;
        }

        .sidebar-menu a i {
            width: 20px;
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }

        .topbar {
            background: var(--bg-secondary);
            height: var(--topbar-height);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
            transition: background-color 0.3s ease;
        }

        .topbar .form-control {
            background: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .topbar .form-control::placeholder {
            color: var(--text-muted);
        }

        .topbar .input-group-text {
            background: var(--input-bg);
            border-color: var(--border-color);
        }

        .content-area {
            padding: 2rem;
        }

        .modern-card {
            background: var(--bg-secondary);
            border-radius: 0;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid var(--border-color);
        }

        .modern-card:hover {
            border-color: var(--bs-primary);
        }

        [data-theme="dark"] .modern-card:hover {
            box-shadow: var(--card-shadow);
        }

        .stat-card {
            background: var(--bg-secondary);
            border-radius: 0;
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            transition: border-color 0.2s ease;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--accent-color, var(--bs-primary));
        }

        .stat-card:hover {
            border-color: var(--bs-primary);
        }

        [data-theme="dark"] .stat-card:hover {
            box-shadow: var(--card-shadow);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            background: var(--accent-soft);
            color: var(--bs-primary);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 0;
            background: var(--bs-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
        }

        [data-theme="dark"] .user-avatar,
        [data-theme="dark"] .btn-primary-modern,
        [data-theme="dark"] .btn-primary-modern:hover {
            color: #07211e;
        }

        .badge-modern {
            padding: 0.4rem 0.8rem;
            border-radius: 0;
            font-size: 0.72rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .preview-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-light);
        }

        .btn-modern {
            padding: 0.625rem 1.5rem;
            border-radius: 0;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary-modern {
            background: var(--bs-primary);
            color: white;
            border: none;
        }

        .btn-primary-modern:hover {
            background: var(--bs-primary-dark);
            color: white;
        }

        .alert-modern {
            border-radius: 0;
            border: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: start;
            gap: 12px;
        }

        .progress-modern {
            height: 6px;
            border-radius: 0;
            background: var(--border-light);
        }

        .progress-bar-modern {
            border-radius: 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block !important;
            }
        }

        .mobile-toggle {
            display: none;
        }

        .table-modern {
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .table-modern thead th {
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
        }

        .table-modern tbody tr {
            background: var(--bg-secondary);
            box-shadow: var(--card-shadow);
            border-radius: 0;
        }

        .table-modern tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
            color: var(--text-primary);
        }

        .table-modern tbody tr td:first-child {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .table-modern tbody tr td:last-child {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        /* Bootstrap Table Dark Mode Override */
        .table {
            --bs-table-bg: var(--bg-secondary);
            --bs-table-color: var(--text-primary);
            --bs-table-border-color: var(--border-color);
            --bs-table-striped-bg: var(--bg-tertiary);
            --bs-table-striped-color: var(--text-primary);
            --bs-table-hover-bg: var(--hover-bg);
            --bs-table-hover-color: var(--text-primary);
        }

        .table > :not(caption) > * > * {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-bottom-color: var(--border-color);
        }

        .table-modern tbody tr {
            background: var(--bg-secondary) !important;
        }

        .table-modern tbody tr:hover {
            background: var(--hover-bg) !important;
        }

        /* Theme Toggle Button */
        .theme-toggle {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle:hover {
            background: var(--hover-bg);
            color: var(--bs-primary);
        }

        .theme-toggle i {
            font-size: 1.25rem;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .topbar .user-name {
            color: var(--text-primary);
        }

        .topbar .user-role {
            color: var(--text-muted);
        }

        /* Modal Styles */
        .modal-backdrop-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content-custom {
            background: var(--bg-secondary);
            border-radius: 0;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 24px 48px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border-color);
        }

        .modal-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title-custom {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .modal-close-btn:hover {
            color: var(--bs-danger);
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 0;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background: var(--input-bg);
            border-color: var(--bs-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 2px rgba(var(--bs-primary-rgb), 0.15);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        /* Input Group Dark Mode */
        .input-group-text {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
        }

        .input-group .form-control {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .input-group .form-control:focus {
            background: var(--input-bg);
            border-color: var(--bs-primary);
            color: var(--text-primary);
        }

        .invalid-feedback {
            color: var(--bs-danger);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Action buttons in table */
        .action-btn {
            background: transparent;
            border: none;
            padding: 0.5rem;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--hover-bg);
        }

        .action-btn-edit {
            color: var(--bs-primary);
        }

        .action-btn-delete {
            color: var(--bs-danger);
        }

        .action-btn-view {
            color: var(--bs-secondary);
        }

        /* Pagination */
        .pagination {
            --bs-pagination-bg: var(--bg-secondary);
            --bs-pagination-color: var(--text-primary);
            --bs-pagination-border-color: var(--border-color);
            --bs-pagination-hover-bg: var(--hover-bg);
            --bs-pagination-hover-color: var(--bs-primary);
            --bs-pagination-focus-bg: var(--hover-bg);
            --bs-pagination-active-bg: var(--bs-primary);
            --bs-pagination-active-border-color: var(--bs-primary);
            --bs-pagination-disabled-bg: var(--bg-tertiary);
            --bs-pagination-disabled-color: var(--text-muted);
        }
    </style>
</head>
<body>
    @php
        $authUser = auth()->user();
        $isAdmin = (bool) $authUser?->isAdmin();
        $isOrganizer = (bool) $authUser?->isOrganizer() && ! $isAdmin;
    @endphp

    <!-- Sidebar -->
    <x-layout.sidebar :brand-name="$brandName" :brand-icon="$brandIcon">
        <x-layout.sidebar-section title="Main">
            @if ($isAdmin)
                <x-layout.sidebar-link href="{{ route('dashboard') }}" icon="fas fa-home" :active="request()->routeIs('dashboard')">Dashboard</x-layout.sidebar-link>
                <x-layout.sidebar-link href="{{ route('admin.users') }}" icon="fas fa-users" :active="request()->routeIs('admin.users')">Users</x-layout.sidebar-link>
                <x-layout.sidebar-link href="{{ route('admin.organizers') }}" icon="fas fa-building" :active="request()->routeIs('admin.organizers*')">Organizer</x-layout.sidebar-link>
                <x-layout.sidebar-link href="{{ route('admin.orders') }}" icon="fas fa-receipt" :active="request()->routeIs('admin.orders*')">Pesanan & E-Tiket</x-layout.sidebar-link>
                <x-layout.sidebar-link href="{{ route('admin.refunds') }}" icon="fas fa-rotate-left" :active="request()->routeIs('admin.refunds*')">Refund</x-layout.sidebar-link>
                <x-layout.sidebar-link href="{{ route('admin.settings') }}" icon="fas fa-sliders" :active="request()->routeIs('admin.settings*')">Pengaturan Ticketing</x-layout.sidebar-link>
            @elseif ($isOrganizer)
                <x-layout.sidebar-link href="{{ route('organizer.dashboard') }}" icon="fas fa-home" :active="request()->routeIs('organizer.dashboard')">Dashboard</x-layout.sidebar-link>
                <x-layout.sidebar-link href="{{ route('organizer.profile') }}" icon="fas fa-building" :active="request()->routeIs('organizer.profile*')">Profil Organizer</x-layout.sidebar-link>
            @endif
        </x-layout.sidebar-section>

        <x-layout.sidebar-section title="Account">
            @if ($isAdmin)
                <x-layout.sidebar-link href="{{ route('admin.profile') }}" icon="fas fa-user-circle" :active="request()->routeIs('admin.profile')">Profile</x-layout.sidebar-link>
                <x-layout.sidebar-link href="#settings" icon="fas fa-cog">Settings</x-layout.sidebar-link>
            @elseif ($isOrganizer)
                <x-layout.sidebar-link href="{{ route('organizer.profile') }}" icon="fas fa-user-circle" :active="request()->routeIs('organizer.profile*')">Profil Organizer</x-layout.sidebar-link>
            @endif
        </x-layout.sidebar-section>

        @if ($isAdmin)
            <x-layout.sidebar-section title="Developer">
                <x-layout.sidebar-link href="{{ route('admin.components') }}" icon="fas fa-cubes" :active="request()->routeIs('admin.components')">Components</x-layout.sidebar-link>
            </x-layout.sidebar-section>
        @endif
    </x-layout.sidebar>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <x-layout.topbar
            :user-name="Auth::user()?->name ?? 'Guest'"
            :user-role="$isAdmin ? 'Administrator' : ($isOrganizer ? 'Organizer' : 'Staff')"
            :notification-count="0"
            :show-logout="true"
            :profile-url="$isOrganizer ? route('organizer.profile') : route('admin.profile')"
        />

        <!-- Content Area -->
        <div class="content-area">
            {{ $slot }}
        </div>
    </div>

    <script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Theme Toggle Functionality
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
            } else if (prefersDark) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }

            updateThemeIcon();
        }

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon();
        }

        function updateThemeIcon() {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const themeIcon = document.getElementById('theme-icon');
            if (themeIcon) {
                themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        // Initialize theme on page load
        initTheme();

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show');
        }
    </script>

    <!-- Toastify & SweetAlert2 -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @if (session('success') || session('error') || session('warning') || session('info'))
        @php
            $toastVariant = session('success') ? 'success' : (session('error') ? 'error' : (session('warning') ? 'warning' : 'info'));
            $toastMessage = session('success') ?? session('error') ?? session('warning') ?? session('info');
        @endphp
        <script>
            window.addEventListener('load', function () {
                const borderColors = {
                    success: "var(--bs-success)",
                    warning: "var(--bs-warning)",
                    error: "var(--bs-danger)",
                    info: "var(--bs-info)"
                };

                Toastify({
                    text: @json($toastMessage),
                    duration: 4000,
                    close: false,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: "var(--bg-secondary)",
                        border: `2px solid ${borderColors[@json($toastVariant)] || "var(--bs-secondary)"}`,
                        color: "var(--text-primary)",
                        borderRadius: "0",
                        boxShadow: "var(--card-shadow)"
                    },
                }).showToast();
            });
        </script>
    @endif

    @stack('scripts')
</body>
</html>
