<?php

use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\TicketController as AccountTicketController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ComponentDocsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\LogoutController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TicketTypeController as AdminTicketTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VenueController as AdminVenueController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController as AuthLogoutController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EventStaffController;
use App\Http\Controllers\Guest\CheckoutController;
use App\Http\Controllers\Guest\EventController;
use App\Http\Controllers\Guest\GuestOrderController;
use App\Http\Controllers\Guest\InfoPageController;
use App\Http\Controllers\Guest\LandingPageController;
use App\Http\Controllers\Guest\OrganizerController as GuestOrganizerController;
use App\Http\Controllers\Guest\PaymentWebhookController;
use App\Http\Controllers\Guest\SitemapController;
use App\Http\Controllers\Guest\XenditRefundWebhookController;
use App\Http\Controllers\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Organizer\ProfileController as OrganizerProfileController;
use App\Http\Controllers\Organizer\RegistrationController as OrganizerRegistrationController;
use App\Http\Controllers\Organizer\TicketTypeController as OrganizerTicketTypeController;
use App\Http\Controllers\ScanController;
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
    ->middleware('throttle:checkout-store')
    ->name('checkout.store');
Route::get('/checkout/{order}', [CheckoutController::class, 'show'])
    ->whereUuid('order')
    ->name('checkout.show');
Route::get('/checkout/{order}/status', [CheckoutController::class, 'status'])
    ->whereUuid('order')
    ->middleware('throttle:checkout-status')
    ->name('checkout.status');
Route::post('/checkout/{order}/pay', [CheckoutController::class, 'pay'])
    ->whereUuid('order')
    ->middleware('throttle:checkout-pay')
    ->name('checkout.pay');

// Webhook gateway pembayaran
Route::post('/webhooks/payment/xendit/refund', XenditRefundWebhookController::class)
    ->middleware('throttle:payment-webhook')
    ->name('webhooks.payment.xendit.refund');

Route::post('/webhooks/payment/{gateway}', PaymentWebhookController::class)
    ->middleware('throttle:payment-webhook')
    ->name('webhooks.payment');

// Scan tiket staff
Route::middleware(['auth', 'active', 'role:admin,staff'])->group(function () {
    Route::get('/scan', [ScanController::class, 'index'])->name('scan.index');
    Route::get('/scan/{event}', [ScanController::class, 'show'])
        ->whereUuid('event')
        ->name('scan.show');
    Route::post('/scan/{event}', [ScanController::class, 'store'])
        ->whereUuid('event')
        ->middleware('throttle:ticket-scan')
        ->name('scan.store');
});

Route::get('/faq', [InfoPageController::class, 'faq'])->name('faq');
Route::get('/tentang-kami', [InfoPageController::class, 'about'])->name('info.about');
Route::get('/privacy-policy', [InfoPageController::class, 'privacy'])->name('info.privacy');
Route::get('/terms', [InfoPageController::class, 'terms'])->name('info.terms');
Route::get('/refund-policy', [InfoPageController::class, 'refund'])->name('info.refund');
Route::get('/panduan-organizer', [InfoPageController::class, 'organizerGuide'])->name('organizer.guide');

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

// Registrasi organizer self-service
Route::middleware(['auth', 'active', 'verified'])
    ->prefix('organizer')
    ->name('organizer.')
    ->group(function () {
        Route::get('/daftar', [OrganizerRegistrationController::class, 'create'])->name('register');
        Route::post('/daftar', [OrganizerRegistrationController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('register.store');
    });

// Organizer Routes
Route::prefix('organizer')->middleware(['auth', 'active', 'role:organizer'])->name('organizer.')->group(function () {
    Route::get('/', OrganizerDashboardController::class)->name('dashboard');
    Route::get('/profil', [OrganizerProfileController::class, 'edit'])->name('profile');
    Route::put('/profil', [OrganizerProfileController::class, 'update'])->name('profile.update');

    Route::get('/events', [OrganizerEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [OrganizerEventController::class, 'create'])->name('events.create');
    Route::post('/events', [OrganizerEventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}/edit', [OrganizerEventController::class, 'edit'])
        ->whereUuid('event')
        ->name('events.edit');
    Route::put('/events/{event}', [OrganizerEventController::class, 'update'])
        ->whereUuid('event')
        ->name('events.update');
    Route::delete('/events/{event}', [OrganizerEventController::class, 'destroy'])
        ->whereUuid('event')
        ->name('events.destroy');
    Route::post('/events/{event}/submit', [OrganizerEventController::class, 'submit'])
        ->whereUuid('event')
        ->name('events.submit');

    Route::get('/events/{event}/laporan', [OrganizerEventController::class, 'report'])
        ->whereUuid('event')
        ->name('events.report');

    Route::get('/events/{event}/staff', [EventStaffController::class, 'index'])
        ->whereUuid('event')
        ->name('events.staff.index');
    Route::post('/events/{event}/staff', [EventStaffController::class, 'store'])
        ->whereUuid('event')
        ->name('events.staff.store');
    Route::delete('/events/{event}/staff/{user}', [EventStaffController::class, 'destroy'])
        ->whereUuid('event')
        ->name('events.staff.destroy');

    Route::get('/events/{event}/tickets', [OrganizerTicketTypeController::class, 'index'])
        ->whereUuid('event')
        ->name('events.tickets.index');
    Route::post('/events/{event}/tickets', [OrganizerTicketTypeController::class, 'store'])
        ->whereUuid('event')
        ->name('events.tickets.store');
    Route::put('/events/{event}/tickets/{ticketType}', [OrganizerTicketTypeController::class, 'update'])
        ->whereUuid('event')
        ->whereUuid('ticketType')
        ->name('events.tickets.update');
    Route::put('/events/{event}/tickets/{ticketType}/sales', [OrganizerTicketTypeController::class, 'updateSales'])
        ->whereUuid('event')
        ->whereUuid('ticketType')
        ->name('events.tickets.sales');
    Route::delete('/events/{event}/tickets/{ticketType}', [OrganizerTicketTypeController::class, 'destroy'])
        ->whereUuid('event')
        ->whereUuid('ticketType')
        ->name('events.tickets.destroy');
});

Route::prefix('admin')->middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)
        ->middleware('role:admin')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('admin.profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('admin.profile.avatar');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('admin.profile.avatar.destroy');

    Route::get('/components', ComponentDocsController::class)->name('admin.components');
    Route::post('/logout', [LogoutController::class, '__invoke'])->name('admin.logout');
    Route::get('/pengaturan', [SettingsController::class, 'edit'])
        ->middleware('role:admin')
        ->name('admin.settings');
    Route::put('/pengaturan', [SettingsController::class, 'update'])
        ->middleware('role:admin')
        ->name('admin.settings.update');

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('admin.users');
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'updateStatus'])->name('admin.users.status');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

        Route::get('/pesanan', [AdminOrderController::class, 'index'])->name('admin.orders');
        Route::get('/pesanan/{order}', [AdminOrderController::class, 'show'])
            ->whereUuid('order')
            ->name('admin.orders.show');
        Route::get('/pesanan/{order}/pdf', [AdminOrderController::class, 'pdf'])
            ->whereUuid('order')
            ->name('admin.orders.pdf');
        Route::post('/pesanan/{order}/kirim-tiket', [AdminOrderController::class, 'resendTicket'])
            ->whereUuid('order')
            ->name('admin.orders.resend-ticket');
        Route::post('/pesanan/{order}/pembayaran/{payment}/rekonsiliasi', [AdminOrderController::class, 'reconcilePayment'])
            ->whereUuid('order')
            ->whereUuid('payment')
            ->name('admin.orders.payments.reconcile');

        Route::get('/organizers', [AdminOrganizerController::class, 'index'])->name('admin.organizers');
        Route::post('/organizers/{organizer}/verify', [AdminOrganizerController::class, 'verify'])
            ->whereUuid('organizer')
            ->name('admin.organizers.verify');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

        Route::get('/venues', [AdminVenueController::class, 'index'])->name('admin.venues');
        Route::post('/venues', [AdminVenueController::class, 'store'])->name('admin.venues.store');
        Route::put('/venues/{venue}', [AdminVenueController::class, 'update'])
            ->whereUuid('venue')
            ->name('admin.venues.update');
        Route::delete('/venues/{venue}', [AdminVenueController::class, 'destroy'])
            ->whereUuid('venue')
            ->name('admin.venues.destroy');

        Route::get('/faqs', [AdminFaqController::class, 'index'])->name('admin.faqs');
        Route::post('/faqs', [AdminFaqController::class, 'store'])->name('admin.faqs.store');
        Route::put('/faqs/{faq}', [AdminFaqController::class, 'update'])->name('admin.faqs.update');
        Route::delete('/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('admin.faqs.destroy');

        Route::get('/banners', [AdminBannerController::class, 'index'])->name('admin.banners');
        Route::post('/banners', [AdminBannerController::class, 'store'])->name('admin.banners.store');
        Route::put('/banners/{banner}', [AdminBannerController::class, 'update'])->name('admin.banners.update');
        Route::delete('/banners/{banner}', [AdminBannerController::class, 'destroy'])->name('admin.banners.destroy');

        Route::get('/events', [AdminEventController::class, 'index'])->name('admin.events');
        Route::get('/events/{event}', [AdminEventController::class, 'show'])
            ->whereUuid('event')
            ->name('admin.events.show');
        Route::post('/events/{event}/approve', [AdminEventController::class, 'approve'])
            ->whereUuid('event')
            ->name('admin.events.approve');
        Route::post('/events/{event}/reject', [AdminEventController::class, 'reject'])
            ->whereUuid('event')
            ->name('admin.events.reject');
        Route::put('/events/{event}/tickets/{ticketType}/sales', [AdminTicketTypeController::class, 'updateSales'])
            ->whereUuid('event')
            ->whereUuid('ticketType')
            ->name('admin.events.tickets.sales');

        Route::get('/events/{event}/staff', [EventStaffController::class, 'index'])
            ->whereUuid('event')
            ->name('admin.events.staff.index');
        Route::post('/events/{event}/staff', [EventStaffController::class, 'store'])
            ->whereUuid('event')
            ->name('admin.events.staff.store');
        Route::delete('/events/{event}/staff/{user}', [EventStaffController::class, 'destroy'])
            ->whereUuid('event')
            ->name('admin.events.staff.destroy');

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
