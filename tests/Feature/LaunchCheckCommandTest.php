<?php

function safeProductionConfig(): void
{
    config([
        'app.env' => 'production',
        'app.debug' => false,
        'app.url' => 'https://ticketing.example.test',
        'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
        'session.secure' => true,
        'queue.default' => 'redis',
        'cache.default' => 'redis',
        'mail.default' => 'smtp',
        'logging.default' => 'stack',
        'logging.channels.single.level' => 'warning',
        'payment.default' => 'midtrans',
        'payment.midtrans.server_key' => 'midtrans-server-key',
        'payment.midtrans.is_production' => true,
        'ticketing.rate_limits.payment_webhook' => 120,
    ]);
}

it('passes when production configuration is safe', function () {
    safeProductionConfig();

    $this->artisan('ticketing:launch-check')
        ->expectsOutputToContain('Launch check lulus')
        ->assertExitCode(0);
});

it('fails when debug, http url, or fake gateway would leak or break production', function () {
    safeProductionConfig();

    config([
        'app.debug' => true,
        'app.url' => 'http://ticketing.example.test',
        'payment.default' => 'fake',
        'queue.default' => 'sync',
        'mail.default' => 'log',
    ]);

    $this->artisan('ticketing:launch-check')
        ->expectsOutputToContain('APP_DEBUG')
        ->expectsOutputToContain('APP_URL')
        ->expectsOutputToContain('PAYMENT_GATEWAY')
        ->expectsOutputToContain('Launch check gagal')
        ->assertExitCode(1);
});

it('passes with a complete xendit production configuration', function () {
    safeProductionConfig();

    config([
        'payment.default' => 'xendit',
        'payment.xendit.secret_key' => 'xnd_production_example',
        'payment.xendit.callback_token' => 'callback-token',
        'payment.xendit.is_production' => true,
    ]);

    $this->artisan('ticketing:launch-check')
        ->expectsOutputToContain('XENDIT_SECRET_KEY')
        ->expectsOutputToContain('XENDIT_CALLBACK_TOKEN')
        ->expectsOutputToContain('Launch check lulus')
        ->assertExitCode(0);
});

it('fails when xendit credentials are missing', function () {
    safeProductionConfig();

    config([
        'payment.default' => 'xendit',
        'payment.xendit.secret_key' => '',
        'payment.xendit.callback_token' => '',
        'payment.xendit.is_production' => false,
    ]);

    $this->artisan('ticketing:launch-check')
        ->expectsOutputToContain('XENDIT_SECRET_KEY')
        ->expectsOutputToContain('XENDIT_CALLBACK_TOKEN')
        ->expectsOutputToContain('XENDIT_IS_PRODUCTION')
        ->expectsOutputToContain('Launch check gagal')
        ->assertExitCode(1);
});

it('treats warnings as failures in strict mode', function () {
    safeProductionConfig();

    config([
        'app.env' => 'staging',
    ]);

    $this->artisan('ticketing:launch-check')
        ->expectsOutputToContain('lulus dengan 2 peringatan')
        ->assertExitCode(0);

    $this->artisan('ticketing:launch-check', ['--strict' => true])
        ->assertExitCode(1);
});
