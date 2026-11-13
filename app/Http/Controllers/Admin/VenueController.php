<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\Admin\MasterDataService;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $venues = Venue::query()
            ->withCount('events')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%')
                        ->orWhere('province', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.venues', [
            'venues' => $venues,
            'search' => $search,
            'status' => $status,
            'editingVenue' => $request->filled('edit')
                ? Venue::findOrFail((string) $request->string('edit'))
                : null,
            'deletingVenue' => $request->filled('delete')
                ? Venue::withCount('events')->findOrFail((string) $request->string('delete'))
                : null,
            'showCreateModal' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request, MasterDataService $masterData): RedirectResponse
    {
        $validated = $this->validateVenue($request);

        $venue = Venue::query()->create([
            'name' => $validated['name'],
            'slug' => $masterData->resolveSlug(Venue::class, $validated['slug'] ?? null, $validated['name']),
            'address' => $validated['address'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'capacity' => $validated['capacity'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'maps_url' => $validated['maps_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        PublicCache::forgetCities();

        $masterData->audit(
            $request->user(),
            'venue.created',
            'venue',
            $venue->getKey(),
            ['name' => $venue->name, 'slug' => $venue->slug, 'city' => $venue->city],
            $request->ip(),
        );

        return redirect()
            ->route('admin.venues')
            ->with('success', 'Venue berhasil dibuat.');
    }

    public function update(
        Request $request,
        Venue $venue,
        MasterDataService $masterData,
    ): RedirectResponse {
        $validated = $this->validateVenue($request);

        $venue->forceFill([
            'name' => $validated['name'],
            'slug' => $masterData->resolveSlug(
                Venue::class,
                $validated['slug'] ?? null,
                $validated['name'],
                $venue->getKey(),
            ),
            'address' => $validated['address'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'capacity' => $validated['capacity'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'maps_url' => $validated['maps_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        PublicCache::forgetCities();

        $masterData->audit(
            $request->user(),
            'venue.updated',
            'venue',
            $venue->getKey(),
            [
                'name' => $venue->name,
                'slug' => $venue->slug,
                'city' => $venue->city,
                'is_active' => $venue->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.venues')
            ->with('success', 'Venue berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        Venue $venue,
        MasterDataService $masterData,
    ): RedirectResponse {
        if ($venue->events()->exists()) {
            throw ValidationException::withMessages([
                'venue' => 'Venue masih digunakan oleh event dan tidak dapat dihapus.',
            ]);
        }

        $snapshot = ['name' => $venue->name, 'slug' => $venue->slug, 'city' => $venue->city];
        $venueId = $venue->getKey();
        $venue->delete();

        PublicCache::forgetCities();

        $masterData->audit(
            $request->user(),
            'venue.deleted',
            'venue',
            $venueId,
            $snapshot,
            $request->ip(),
        );

        return redirect()
            ->route('admin.venues')
            ->with('success', 'Venue berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVenue(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'maps_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'Nama venue wajib diisi.',
            'address.required' => 'Alamat venue wajib diisi.',
            'city.required' => 'Kota wajib diisi.',
            'province.required' => 'Provinsi wajib diisi.',
            'capacity.integer' => 'Kapasitas harus berupa angka.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
            'maps_url.url' => 'URL maps harus berupa URL yang valid.',
        ]);
    }
}
