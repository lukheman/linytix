@props([
    'userName' => 'John Doe',
    'userRole' => 'Administrator',
    'userInitials' => null,
    'notificationCount' => 0,
    'searchPlaceholder' => 'Search anything...',
    'showLogout' => true,
    'showThemeToggle' => true,
    'profileUrl' => null
])

@php
    // Generate initials from user name if not provided
    $initials = $userInitials;
    if (!$initials && $userName) {
        $words = explode(' ', $userName);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        $initials = substr($initials, 0, 2);
    }
@endphp

<div class="topbar">
    <div class="d-flex align-items-center">
        <button class="btn btn-link mobile-toggle me-3" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="input-group" style="max-width: 400px;">
            <span class="input-group-text" style="background: var(--input-bg); border-color: var(--border-color);">
                <i class="fas fa-search" style="color: var(--text-muted);"></i>
            </span>
            <input type="text" class="form-control" placeholder="{{ $searchPlaceholder }}" style="background: var(--input-bg); border-color: var(--border-color); color: var(--text-primary);">
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        @if($showThemeToggle)
            <button class="theme-toggle" onclick="toggleTheme()" title="Toggle theme">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        @endif
        <button class="btn btn-link position-relative">
            <i class="fas fa-bell" style="color: var(--text-secondary); font-size: 1.25rem;"></i>
            @if($notificationCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">{{ $notificationCount }}</span>
            @endif
        </button>
        <a href="{{ $profileUrl ?? route('admin.profile') }}" class="d-flex align-items-center gap-2 text-decoration-none" title="Go to Profile">
            @if(Auth::user()?->hasAvatar())
                <img src="{{ Auth::user()->avatarUrl() }}" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
            @else
                <div class="user-avatar">{{ $initials }}</div>
            @endif
            <div class="d-none d-md-block">
                <div class="fw-semibold user-name">{{ $userName }}</div>
                <small class="user-role">{{ $userRole }}</small>
            </div>
        </a>
        @if($showLogout)
            <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link" title="Logout" style="color: var(--text-secondary);">
                    <i class="fas fa-sign-out-alt" style="font-size: 1.25rem;"></i>
                </button>
            </form>
        @endif
        {{ $actions ?? '' }}
    </div>
</div>
