<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Support\SiteContent;
use Illuminate\View\View;

class InfoPageController extends Controller
{
    public function faq(): View
    {
        return view('events.info', [
            'pageKey' => 'faq',
            'faqs' => Faq::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'settings' => SiteContent::settings(),
            'seo' => [
                'title' => 'FAQ — '.$this->siteName(),
                'description' => 'Pertanyaan yang sering diajukan tentang pembelian tiket dan penggunaan e-tiket.',
            ],
        ]);
    }

    public function about(): View
    {
        return $this->page('about', 'Tentang Kami', 'Kenali platform tiket event yang menghubungkan penonton dengan pengalaman terbaik.');
    }

    public function privacy(): View
    {
        return $this->page('privacy', 'Kebijakan Privasi', 'Bagaimana kami mengumpulkan, menggunakan, dan melindungi data pribadi Anda.');
    }

    public function terms(): View
    {
        return $this->page('terms', 'Syarat & Ketentuan', 'Ketentuan penggunaan layanan pembelian tiket online.');
    }

    public function refund(): View
    {
        return $this->page('refund', 'Kebijakan Refund', 'Ketentuan pengembalian dana untuk pembelian tiket.');
    }

    private function page(string $key, string $title, string $description): View
    {
        return view('events.info', [
            'pageKey' => $key,
            'faqs' => collect(),
            'pageTitle' => $title,
            'settings' => SiteContent::settings(),
            'seo' => [
                'title' => $title.' — '.$this->siteName(),
                'description' => $description,
            ],
        ]);
    }

    private function siteName(): string
    {
        return SiteContent::settings()['site_name'] ?? 'LinyTix';
    }
}
