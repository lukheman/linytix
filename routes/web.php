<?php

use App\Http\Controllers\Admin\ComponentDocsController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Guest\EventController;
use App\Http\Controllers\Guest\InfoPageController;
use App\Http\Controllers\Guest\LandingPageController;
use App\Http\Controllers\Guest\PublicPageController;
use App\Http\Controllers\Guest\SitemapController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::get('/', LandingPageController::class)->name('home');
Route::redirect('/admin', '/admin/dashboard')->middleware('auth')->name('admin');
Route::get('/admin/login', [LoginController::class, 'show'])->name('admin.login');
Route::get('/tentang', [PublicPageController::class, 'about'])->name('about');
Route::get('/layanan', [PublicPageController::class, 'services'])->name('services');
Route::get('/layanan/{service:slug}', [PublicPageController::class, 'service'])->name('services.show');
Route::get('/kerja-sama', [PublicPageController::class, 'collaborations'])->name('collaborations');
Route::get('/kerja-sama/{collaboration:slug}', [PublicPageController::class, 'collaboration'])->name('collaborations.show');
Route::get('/testimoni', [PublicPageController::class, 'testimonials'])->name('testimonials');
Route::get('/kontak', [PublicPageController::class, 'contact'])->name('contact');

// Ticketing public routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');

Route::get('/faq', [InfoPageController::class, 'faq'])->name('faq');
Route::get('/tentang-kami', [InfoPageController::class, 'about'])->name('info.about');
Route::get('/privacy-policy', [InfoPageController::class, 'privacy'])->name('info.privacy');
Route::get('/terms', [InfoPageController::class, 'terms'])->name('info.terms');
Route::get('/refund-policy', [InfoPageController::class, 'refund'])->name('info.refund');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Auth Routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

Route::prefix('admin')->middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('admin.users');
    Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('admin.profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('admin.profile.avatar.destroy');

    Route::get('/components', ComponentDocsController::class)->name('admin.components');
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('logout');
    Route::get('/content/{resource}', [ContentController::class, 'index'])->name('admin.content.index');
    Route::get('/content/{resource}/create', [ContentController::class, 'create'])->name('admin.content.create');
    Route::post('/content/{resource}', [ContentController::class, 'store'])->name('admin.content.store');
    Route::get('/content/{resource}/{id}/edit', [ContentController::class, 'edit'])->name('admin.content.edit');
    Route::put('/content/{resource}/{id}', [ContentController::class, 'update'])->name('admin.content.update');
    Route::delete('/content/{resource}/{id}', [ContentController::class, 'destroy'])->name('admin.content.destroy');
    Route::get('/pengaturan', [ContentController::class, 'settings'])->name('admin.settings');
    Route::put('/pengaturan', [ContentController::class, 'updateSettings'])->name('admin.settings.update');
});
