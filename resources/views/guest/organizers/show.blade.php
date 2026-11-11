<x-layouts.public
    :title="$seo['title']"
    :description="$seo['description']"
    :settings="$settings"
    :canonical="route('organizers.show', $organizer->slug)"
>
    @push('styles')
        <style>
            .event-poster { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: var(--mint); }
            .event-poster img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
            .event-card:hover .event-poster img { transform: scale(1.04); }
            .event-badge { position: absolute; top: .85rem; right: .85rem; background: var(--teal); color: #fff; border-radius: 999px; padding: .3rem .75rem; font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
            .event-badge.badge-sold { background: #6c757d; }
            .organizer-logo { width: 96px; height: 96px; border-radius: 1.25rem; object-fit: cover; background: var(--mint); display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 800; color: var(--teal); }
        </style>
    @endpush

    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="card-clean p-4 p-lg-5 mb-4">
                <div class="d-flex flex-wrap align-items-start gap-4">
                    @if ($organizer->logoUrl())
                        <img src="{{ $organizer->logoUrl() }}" alt="Logo {{ $organizer->name }}" class="organizer-logo">
                    @else
                        <div class="organizer-logo">{{ mb_substr($organizer->name, 0, 1) }}</div>
                    @endif

                    <div class="flex-grow-1">
                        <div class="eyebrow">Organizer</div>
                        <h1 class="display-6 mb-2">{{ $organizer->name }}</h1>

                        @if ($organizer->is_verified)
                            <div class="small text-success mb-2">
                                <i class="fa-solid fa-circle-check me-1"></i>Organizer terverifikasi
                            </div>
                        @endif

                        @if ($organizer->description)
                            <p class="text-secondary mb-3">{{ $organizer->description }}</p>
                        @endif

                        <div class="d-flex flex-wrap gap-3 small text-secondary">
                            @if ($organizer->contact_email)
                                <span><i class="fa-regular fa-envelope me-1"></i>{{ $organizer->contact_email }}</span>
                            @endif
                            @if ($organizer->contact_phone)
                                <span><i class="fa-solid fa-phone me-1"></i>{{ $organizer->contact_phone }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <div class="eyebrow">Event mendatang</div>
                    <h2 class="h3 mb-0">Diselenggarakan oleh {{ $organizer->name }}</h2>
                </div>
                <a href="{{ route('events.index') }}" class="btn btn-ghost btn-sm">Semua event</a>
            </div>

            @if ($events->isEmpty())
                <div class="card-clean p-5 text-center text-secondary">
                    Belum ada event mendatang dari organizer ini.
                </div>
            @else
                <div class="row g-4">
                    @foreach ($events as $event)
                        <div class="col-md-6 col-lg-4">
                            <x-events.card :event="$event" />
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
