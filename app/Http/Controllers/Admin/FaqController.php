<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\Admin\MasterDataService;
use App\Support\PublicCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $faqs = Faq::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('question', 'like', '%'.$search.'%')
                        ->orWhere('answer', 'like', '%'.$search.'%');
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('question')
            ->paginate(15)
            ->withQueryString();

        return view('admin.master.faqs', [
            'faqs' => $faqs,
            'search' => $search,
            'status' => $status,
            'editingFaq' => $request->filled('edit')
                ? Faq::findOrFail($request->integer('edit'))
                : null,
            'deletingFaq' => $request->filled('delete')
                ? Faq::findOrFail($request->integer('delete'))
                : null,
            'showCreateModal' => $request->boolean('create'),
        ]);
    }

    public function store(Request $request, MasterDataService $masterData): RedirectResponse
    {
        $validated = $this->validateFaq($request);

        $faq = Faq::query()->create([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        PublicCache::forgetFaqs();

        $masterData->audit(
            $request->user(),
            'faq.created',
            'faq',
            $faq->getKey(),
            ['question' => $faq->question],
            $request->ip(),
        );

        return redirect()
            ->route('admin.faqs')
            ->with('success', 'FAQ berhasil dibuat.');
    }

    public function update(
        Request $request,
        Faq $faq,
        MasterDataService $masterData,
    ): RedirectResponse {
        $validated = $this->validateFaq($request);

        $faq->forceFill([
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        PublicCache::forgetFaqs();

        $masterData->audit(
            $request->user(),
            'faq.updated',
            'faq',
            $faq->getKey(),
            [
                'question' => $faq->question,
                'is_active' => $faq->is_active,
            ],
            $request->ip(),
        );

        return redirect()
            ->route('admin.faqs')
            ->with('success', 'FAQ berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        Faq $faq,
        MasterDataService $masterData,
    ): RedirectResponse {
        $snapshot = ['question' => $faq->question];
        $faq->delete();

        PublicCache::forgetFaqs();

        $masterData->audit(
            $request->user(),
            'faq.deleted',
            'faq',
            (string) $faq->getKey(),
            $snapshot,
            $request->ip(),
        );

        return redirect()
            ->route('admin.faqs')
            ->with('success', 'FAQ berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFaq(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'answer' => ['required', 'string', 'max:20000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'is_active' => ['required', 'boolean'],
        ], [
            'question.required' => 'Pertanyaan wajib diisi.',
            'answer.required' => 'Jawaban wajib diisi.',
        ]);
    }
}
