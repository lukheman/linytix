<?php

use Illuminate\Support\Facades\Mail;

it('sends a test email through the configured mailer', function () {
    Mail::fake();
    config(['mail.default' => 'smtp']);

    $this->artisan('ticketing:mail-test', ['email' => 'buyer@example.test'])
        ->expectsOutputToContain('Email uji selesai diproses')
        ->assertExitCode(0);
});

it('rejects an invalid recipient address', function () {
    Mail::fake();

    $this->artisan('ticketing:mail-test', ['email' => 'bukan-email'])
        ->expectsOutputToContain('Alamat email tidak valid')
        ->assertExitCode(1);
});

it('warns when the log mailer cannot deliver real email', function () {
    Mail::fake();
    config(['mail.default' => 'log']);

    $this->artisan('ticketing:mail-test', ['email' => 'buyer@example.test'])
        ->expectsOutputToContain('tidak mengirim email nyata')
        ->expectsOutputToContain('Email uji selesai diproses')
        ->assertExitCode(0);
});
