<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $events = Event::query()
            ->visible()
            ->orderBy('start_at')
            ->get(['slug', 'updated_at']);

        return response()
            ->view('events.sitemap', ['events' => $events])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
