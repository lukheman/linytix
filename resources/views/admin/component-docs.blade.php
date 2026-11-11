<x-layouts.app title="Component Documentation - LinyTix">
    {{-- Page Header --}}
    <div class="docs-header mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <h1 class="docs-title">
                    <i class="fas fa-cubes me-2" style="color: var(--bs-primary);"></i>
                    Component Library
                </h1>
                <p class="docs-subtitle mb-0">Comprehensive UI component documentation with live examples</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <x-ui.badge variant="primary" icon="fas fa-cubes">33 Components</x-ui.badge>
                <x-ui.badge variant="success" icon="fas fa-moon">Dark Mode Ready</x-ui.badge>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Sidebar Navigation --}}
        <div class="col-lg-3">
            <div class="docs-sidebar position-sticky" style="top: 90px;">
                {{-- Search --}}
                <div class="docs-search mb-3">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control" id="docs-search" placeholder="Search components..."
                            autocomplete="off">
                    </div>
                </div>

                {{-- Navigation --}}
                <nav class="docs-nav">
                    @foreach($sections as $key => $section)
                        <a href="{{ route('admin.components', ['section' => $key]) }}"
                            class="docs-nav-item {{ $activeSection === $key ? 'active' : '' }}">
                            <div class="d-flex align-items-center gap-2">
                                <i class="{{ $section['icon'] }}"></i>
                                <span>{{ $section['label'] }}</span>
                            </div>
                            <span class="docs-nav-count">{{ $section['count'] }}</span>
                        </a>
                    @endforeach
                </nav>

                {{-- Quick Stats --}}
                <div class="docs-stats mt-4">
                    <div class="docs-stat">
                        <div class="docs-stat-value">33</div>
                        <div class="docs-stat-label">Components</div>
                    </div>
                    <div class="docs-stat">
                        <div class="docs-stat-value">11</div>
                        <div class="docs-stat-label">Categories</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div class="col-lg-9">
            {{-- Buttons Section --}}
            @if($activeSection === 'buttons')
                @include('admin.partials.docs-buttons')
            @endif

            {{-- Badges Section --}}
            @if($activeSection === 'badges')
                @include('admin.partials.docs-badges')
            @endif

            {{-- Alerts Section --}}
            @if($activeSection === 'alerts')
                @include('admin.partials.docs-alerts')
            @endif

            {{-- Forms Section --}}
            @if($activeSection === 'forms')
                @include('admin.partials.docs-forms')
            @endif

            {{-- Selects Section --}}
            @if($activeSection === 'selects')
                @include('admin.partials.docs-selects')
            @endif

            {{-- Toggles Section --}}
            @if($activeSection === 'toggles')
                @include('admin.partials.docs-toggles')
            @endif

            {{-- Cards Section --}}
            @if($activeSection === 'cards')
                @include('admin.partials.docs-cards')
            @endif

            {{-- Tables Section --}}
            @if($activeSection === 'tables')
                @include('admin.partials.docs-tables')
            @endif

            {{-- Navigation Section --}}
            @if($activeSection === 'navigation')
                @include('admin.partials.docs-navigation')
            @endif

            {{-- Feedback Section --}}
            @if($activeSection === 'feedback')
                @include('admin.partials.docs-feedback')
            @endif

            {{-- Media Section --}}
            @if($activeSection === 'media')
                @include('admin.partials.docs-media')
            @endif
        </div>
    </div>

<style>
    .docs-header {
        padding: 1.5rem 2rem;
        background: var(--bs-primary);
        border-radius: 20px;
        color: white;
    }

    .docs-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.25rem;
    }

    .docs-subtitle {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.95rem;
    }

    .docs-sidebar {
        background: var(--bs-body-bg);
        border-radius: 16px;
        padding: 1.25rem;
        box-shadow: var(--bs-box-shadow);
    }

    .docs-search .input-group-text {
        background: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-tertiary-color);
    }

    .docs-search .form-control {
        background: var(--bs-body-bg);
        border-color: var(--bs-border-color);
        color: var(--bs-body-color);
    }

    .docs-nav {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .docs-nav-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: transparent;
        border: none;
        text-align: left;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        color: var(--bs-secondary-color);
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
        width: 100%;
    }

    .docs-nav-item:hover {
        background: var(--bs-tertiary-bg);
        color: var(--bs-primary);
    }

    .docs-nav-item.active {
        background: var(--bs-primary);
        color: white;
    }

    .docs-nav-item.active .docs-nav-count {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .docs-nav-item i {
        width: 20px;
        font-size: 0.9rem;
    }

    .docs-nav-count {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        background: var(--bs-tertiary-bg);
        border-radius: 20px;
        color: var(--bs-tertiary-color);
    }

    .docs-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        padding: 1rem;
        background: var(--bs-tertiary-bg);
        border-radius: 12px;
    }

    .docs-stat {
        text-align: center;
    }

    .docs-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--bs-primary);
    }

    .docs-stat-label {
        font-size: 0.75rem;
        color: var(--bs-tertiary-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Component Card */
    .docs-card {
        background: var(--bs-body-bg);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--bs-box-shadow);
        margin-bottom: 1.5rem;
        border: 1px solid var(--bs-border-color);
    }

    .docs-card-header {
        padding: 1rem 1.25rem;
        background: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .docs-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--bs-body-color);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .docs-card-title i {
        color: var(--bs-primary);
    }

    .docs-card-actions {
        display: flex;
        gap: 0.5rem;
    }

    .docs-copy-btn {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        color: var(--bs-secondary-color);
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .docs-copy-btn:hover {
        background: var(--bs-primary);
        border-color: var(--bs-primary);
        color: white;
    }

    .docs-code {
        background: #0f172a;
        padding: 1.25rem;
        overflow-x: auto;
        position: relative;
    }

    .docs-code pre {
        margin: 0;
    }

    .docs-code code {
        font-family: 'Fira Code', 'Monaco', 'Consolas', monospace;
        font-size: 0.8125rem;
        line-height: 1.6;
        color: #e2e8f0;
        white-space: pre;
    }

    /* Syntax Highlighting */
    .docs-code .tag {
        color: #f472b6;
    }

    .docs-code .attr {
        color: #7dd3fc;
    }

    .docs-code .string {
        color: #86efac;
    }

    .docs-code .comment {
        color: #64748b;
    }

    .docs-preview {
        padding: 2rem;
        background: var(--bs-body-bg);
    }

    .docs-preview-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--bs-tertiary-color);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .docs-preview-label::before {
        content: '';
        width: 8px;
        height: 8px;
        background: var(--bs-success);
        border-radius: 50%;
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* Section Header */
    .docs-section-header {
        margin-bottom: 1.5rem;
    }

    .docs-section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--bs-body-color);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .docs-section-title i {
        color: var(--bs-primary);
        font-size: 1.25rem;
    }

    .docs-section-desc {
        color: var(--bs-tertiary-color);
        margin-bottom: 0;
    }

    /* Props Table */
    .docs-props-table {
        width: 100%;
        border-collapse: collapse;
    }

    .docs-props-table th,
    .docs-props-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .docs-props-table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--bs-tertiary-color);
        font-weight: 600;
    }

    .docs-props-table td {
        color: var(--bs-body-color);
        font-size: 0.875rem;
    }

    .docs-props-table code {
        background: var(--bs-tertiary-bg);
        padding: 0.125rem 0.5rem;
        border-radius: 4px;
        font-size: 0.8rem;
        color: var(--bs-primary);
    }

    .docs-prop-required {
        color: var(--bs-danger);
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 0.25rem;
    }
</style>

<script>
    function copyCode(button) {
        const codeBlock = button.closest('.docs-card').querySelector('code');
        const text = codeBlock.textContent;

        navigator.clipboard.writeText(text).then(() => {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i> Copied!';
            button.style.background = 'var(--bs-success)';
            button.style.color = 'white';

            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
                button.style.color = '';
            }, 2000);
        });
    }

    const docsSearch = document.getElementById('docs-search');

    if (docsSearch) {
        docsSearch.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();

            document.querySelectorAll('.docs-nav-item').forEach((item) => {
                item.classList.toggle('d-none', query !== '' && !item.textContent.toLowerCase().includes(query));
            });
        });
    }
</script>
</x-layouts.app>
