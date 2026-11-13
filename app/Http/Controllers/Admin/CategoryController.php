<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Admin\MasterDataService;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $categories = Category::query()
            ->withCount('events')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.categories', [
            'categories' => $categories,
            'search' => $search,
            'status' => $status,
            'editingCategory' => $request->filled('edit')
                ? Category::findOrFail($request->integer('edit'))
                : null,
            'deletingCategory' => $request->filled('delete')
                ? Category::withCount('events')->findOrFail($request->integer('delete'))
                : null,
            'showCreateModal' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request, MasterDataService $masterData): RedirectResponse
    {
        $validated = $this->validateCategory($request);

        $category = Category::query()->create([
            'name' => $validated['name'],
            'slug' => $masterData->resolveSlug(Category::class, $validated['slug'] ?? null, $validated['name']),
            'icon_url' => $validated['icon_url'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        PublicCache::forgetCategories();

        $masterData->audit(
            $request->user(),
            'category.created',
            'category',
            $category->getKey(),
            ['name' => $category->name, 'slug' => $category->slug],
            $request->ip(),
        );

        return redirect()
            ->route('admin.categories')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    public function update(
        Request $request,
        Category $category,
        MasterDataService $masterData,
    ): RedirectResponse {
        $validated = $this->validateCategory($request, $category);

        $category->forceFill([
            'name' => $validated['name'],
            'slug' => $masterData->resolveSlug(
                Category::class,
                $validated['slug'] ?? null,
                $validated['name'],
                $category->getKey(),
            ),
            'icon_url' => $validated['icon_url'] ?? null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        PublicCache::forgetCategories();

        $masterData->audit(
            $request->user(),
            'category.updated',
            'category',
            $category->getKey(),
            [
                'name' => $category->name,
                'slug' => $category->slug,
                'is_active' => $category->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.categories')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        Category $category,
        MasterDataService $masterData,
    ): RedirectResponse {
        if ($category->events()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori masih digunakan oleh event dan tidak dapat dihapus.',
            ]);
        }

        $snapshot = ['name' => $category->name, 'slug' => $category->slug];
        $categoryId = $category->getKey();
        $category->delete();

        PublicCache::forgetCategories();

        $masterData->audit(
            $request->user(),
            'category.deleted',
            'category',
            $categoryId,
            $snapshot,
            $request->ip(),
        );

        return redirect()
            ->route('admin.categories')
            ->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'icon_url' => ['nullable', 'url', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'is_active' => ['required', 'boolean'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Nama kategori sudah digunakan.',
            'icon_url.url' => 'URL ikon harus berupa URL yang valid.',
        ]);
    }
}
