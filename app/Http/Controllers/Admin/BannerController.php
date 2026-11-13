<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Event;
use App\Services\Admin\MasterDataService;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $banners = Banner::query()
            ->with('event')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('image_url', 'like', '%'.$search.'%')
                        ->orWhere('link_url', 'like', '%'.$search.'%')
                        ->orWhereHas('event', fn ($event) => $event->where('title', 'like', '%'.$search.'%'));
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.banners', [
            'banners' => $banners,
            'events' => Event::query()->orderBy('title')->get(['id', 'title', 'status']),
            'search' => $search,
            'status' => $status,
            'editingBanner' => $request->filled('edit')
                ? Banner::findOrFail($request->integer('edit'))
                : null,
            'deletingBanner' => $request->filled('delete')
                ? Banner::with('event')->findOrFail($request->integer('delete'))
                : null,
            'showCreateModal' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request, MasterDataService $masterData): RedirectResponse
    {
        $validated = $this->validateBanner($request);

        $banner = Banner::query()->create([
            'event_id' => $validated['event_id'] ?? null,
            'image_url' => $validated['image_url'],
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        PublicCache::forgetBanners();

        $masterData->audit(
            $request->user(),
            'banner.created',
            'banner',
            $banner->getKey(),
            ['image_url' => $banner->image_url, 'event_id' => $banner->event_id],
            $request->ip(),
        );

        return redirect()
            ->route('admin.banners')
            ->with('success', 'Banner berhasil dibuat.');
    }

    public function update(
        Request $request,
        Banner $banner,
        MasterDataService $masterData,
    ): RedirectResponse {
        $validated = $this->validateBanner($request);

        $banner->forceFill([
            'event_id' => $validated['event_id'] ?? null,
            'image_url' => $validated['image_url'],
            'link_url' => $validated['link_url'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        PublicCache::forgetBanners();

        $masterData->audit(
            $request->user(),
            'banner.updated',
            'banner',
            $banner->getKey(),
            [
                'image_url' => $banner->image_url,
                'event_id' => $banner->event_id,
                'is_active' => $banner->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.banners')
            ->with('success', 'Banner berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        Banner $banner,
        MasterDataService $masterData,
    ): RedirectResponse {
        $snapshot = ['image_url' => $banner->image_url, 'event_id' => $banner->event_id];
        $banner->delete();

        PublicCache::forgetBanners();

        $masterData->audit(
            $request->user(),
            'banner.deleted',
            'banner',
            (string) $banner->getKey(),
            $snapshot,
            $request->ip(),
        );

        return redirect()
            ->route('admin.banners')
            ->with('success', 'Banner berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBanner(Request $request): array
    {
        $rules = [
            'event_id' => ['nullable', 'uuid', 'exists:events,id'],
            'image_url' => ['required', 'url', 'max:2048'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];

        if ($request->filled('start_at')) {
            $rules['end_at'][] = 'after_or_equal:start_at';
        }

        return $request->validate($rules, [
            'image_url.required' => 'URL gambar banner wajib diisi.',
            'image_url.url' => 'URL gambar banner harus berupa URL yang valid.',
            'link_url.url' => 'URL tautan harus berupa URL yang valid.',
            'end_at.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);
    }
}
