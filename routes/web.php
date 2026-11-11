<?php

use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\TicketController as AccountTicketController;
use App\Http\Controllers\Admin\ComponentDocsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController as AuthLogoutController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Guest\CheckoutController;
use App\Http\Controllers\Guest\EventController;
use App\Http\Controllers\Guest\GuestOrderController;
use App\Http\Controllers\Guest\InfoPageController;
use App\Http\Controllers\Guest\LandingPageController;
use App\Http\Controllers\Guest\OrganizerController as GuestOrganizerController;
use App\Http\Controllers\Guest\PaymentWebhookController;
use App\Http\Controllers\Guest\SitemapController;
use App\Http\Controllers\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Organizer\ProfileController as OrganizerProfileController;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::get('/', LandingPageController::class)->name('home');
Route::redirect('/admin', '/admin/dashboard')->middleware('auth')->name('admin');
Route::get('/admin/login', [LoginController::class, 'show'])->name('admin.login');
// Ticketing public routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/organizers/{slug}', [GuestOrganizerController::class, 'show'])->name('organizers.show');

// Checkout & payment
Route::get('/events/{event:slug}/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/events/{event:slug}/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('checkout.store');
Route::get('/checkout/{order}', [CheckoutController::class, 'show'])
    ->whereUuid('order')
    ->name('checkout.show');
Route::get('/checkout/{order}/status', [CheckoutController::class, 'status'])
    ->whereUuid('order')
    ->middleware('throttle:120,1')
    ->name('checkout.status');
Route::post('/checkout/{order}/pay', [CheckoutController::class, 'pay'])
    ->whereUuid('order')
    ->middleware('throttle:10,1')
    ->name('checkout.pay');

// Webhook gateway pembayaran
Route::post('/webhooks/payment/{gateway}', PaymentWebhookController::class)
    ->name('webhooks.payment');

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
Route::post('/logout', AuthLogoutController::class)->middleware('auth')->name('logout');

Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');

Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// Account Routes
Route::prefix('akun')->middleware(['auth', 'active'])->name('akun.')->group(function () {
    Route::get('/', AccountDashboardController::class)->name('dashboard');
    Route::get('/pesanan', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
    Route::get('/pesanan/{order}/pdf', [AccountOrderController::class, 'pdf'])->name('orders.pdf');
    Route::get('/tiket', [AccountTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tiket/{ticket}', [AccountTicketController::class, 'show'])->name('tickets.show');
    Route::get('/tiket/{ticket}/pdf', [AccountTicketController::class, 'pdf'])->name('tickets.pdf');
});

// Guest order access
Route::get('/pesanan/cari', [GuestOrderController::class, 'lookupForm'])->name('guest.orders.lookup');
Route::post('/pesanan/cari', [GuestOrderController::class, 'lookup'])->name('guest.orders.lookup.store');
Route::get('/pesanan/{order}', [GuestOrderController::class, 'show'])
    ->whereUuid('order')
    ->name('guest.orders.show');
Route::get('/pesanan/{order}/pdf', [GuestOrderController::class, 'pdf'])
    ->whereUuid('order')
    ->name('guest.orders.pdf');

// Organizer Routes
Route::prefix('organizer')->middleware(['auth', 'active', 'role:organizer'])->name('organizer.')->group(function () {
    Route::get('/', OrganizerDashboardController::class)->name('dashboard');
    Route::get('/profil', [OrganizerProfileController::class, 'edit'])->name('profile');
    Route::put('/profil', [OrganizerProfileController::class, 'update'])->name('profile.update');
});

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
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('admin.logout');
    Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('admin.settings');
    Route::put('/pengaturan', [SettingsController::class, 'update'])->name('admin.settings.update');

    Route::middleware('role:admin')->group(function () {
        Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('admin.orders');
        Route::get('/pesanan/{order}/pdf', [AdminOrderController::class, 'pdf'])
            ->whereUuid('order')
            ->name('admin.orders.pdf');
        Route::post('/pesanan/{order}/kirim-tiket', [AdminOrderController::class, 'resendTicket'])
            ->whereUuid('order')
            ->name('admin.orders.resend-ticket');

        Route::get('/organizers', [AdminOrganizerController::class, 'index'])->name('admin.organizers');
        Route::post('/organizers/{organizer}/verify', [AdminOrganizerController::class, 'verify'])
            ->whereUuid('organizer')
            ->name('admin.organizers.verify');

        Route::get('/refunds', [AdminRefundController::class, 'index'])->name('admin.refunds');
        Route::post('/pesanan/{order}/refund', [AdminRefundController::class, 'store'])
            ->whereUuid('order')
            ->name('admin.orders.refund');
        Route::post('/refunds/{refund}/retry', [AdminRefundController::class, 'retry'])
            ->whereUuid('refund')
            ->name('admin.refunds.retry');
        Route::post('/events/cancel', [AdminRefundController::class, 'cancelEvent'])
            ->name('admin.events.cancel');
    });
});
