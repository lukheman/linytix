<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TicketingLaunchCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticketing:launch-check {--strict : Perlakukan peringatan sebagai kegagalan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Periksa konfigurasi produksi sebelum launch (HTTPS, queue, mail, payment, cache, log)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = $this->checks();
        $failures = 0;
        $warnings = 0;

        $this->newLine();
        $this->line('  <options=bold>LinyTix — Pemeriksaan Konfigurasi Launch</>');
        $this->newLine();

        foreach ($checks as $check) {
            [$status, $label, $detail] = $check;

            $badge = match ($status) {
                'PASS' => '<fg=green;options=bold>[PASS]</>',
                'WARN' => '<fg=yellow;options=bold>[WARN]</>',
                default => '<fg=red;options=bold>[FAIL]</>',
            };

            $this->line(sprintf('  %s %-34s %s', $badge, $label, $detail));

            if ($status === 'FAIL') {
                $failures++;
            } elseif ($status === 'WARN') {
                $warnings++;
            }
        }

        $this->newLine();

        if ($failures > 0) {
            $this->error(sprintf('Launch check gagal: %d item harus diperbaiki sebelum go-live.', $failures));

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->warn(sprintf('Launch check lulus dengan %d peringatan. Tinjau sebelum go-live.', $warnings));

            return $this->option('strict') ? self::FAILURE : self::SUCCESS;
        }

        $this->info('Launch check lulus. Konfigurasi siap untuk produksi.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    private function checks(): array
    {
        $gateway = (string) config('payment.default', 'fake');
        $checks = [
            $this->check(
                config('app.env') === 'production',
                'APP_ENV',
                'production',
                (string) config('app.env'),
                warningOnly: true,
            ),
            $this->check(
                config('app.debug') === false,
                'APP_DEBUG',
                'false',
                config('app.debug') ? 'true' : 'false',
            ),
            $this->check(
                str_starts_with((string) config('app.url'), 'https://'),
                'APP_URL',
                'https://...',
                (string) config('app.url'),
            ),
            $this->check(
                filled(config('app.key')),
                'APP_KEY',
                'terisi',
                filled(config('app.key')) ? 'terisi' : 'kosong',
            ),
            $this->check(
                config('session.secure') === true,
                'SESSION_SECURE_COOKIE',
                'true',
                config('session.secure') ? 'true' : 'false/null',
            ),
            $this->check(
                config('queue.default') !== 'sync',
                'QUEUE_CONNECTION',
                'redis/database/sqs',
                (string) config('queue.default'),
            ),
            $this->check(
                ! in_array(config('cache.default'), ['array', 'null'], true),
                'CACHE_STORE',
                'redis/database/file',
                (string) config('cache.default'),
            ),
            $this->check(
                ! in_array(config('mail.default'), ['log', 'array'], true),
                'MAIL_MAILER',
                'smtp/resend/mailgun',
                (string) config('mail.default'),
            ),
            $this->check(
                ! in_array(mb_strtolower((string) $this->logLevel()), ['debug'], true),
                'LOG_LEVEL',
                'warning/error',
                $this->logLevel(),
            ),
            $this->check(
                $gateway !== 'fake',
                'PAYMENT_GATEWAY',
                'midtrans/xendit',
                $gateway,
            ),
        ];

        if ($gateway === 'xendit') {
            $checks[] = $this->check(
                filled(config('payment.xendit.secret_key')),
                'XENDIT_SECRET_KEY',
                'terisi',
                filled(config('payment.xendit.secret_key')) ? 'terisi' : 'kosong',
            );

            $checks[] = $this->check(
                filled(config('payment.xendit.callback_token')),
                'XENDIT_CALLBACK_TOKEN',
                'terisi',
                filled(config('payment.xendit.callback_token')) ? 'terisi' : 'kosong',
            );

            $checks[] = $this->check(
                config('payment.xendit.is_production') === true,
                'XENDIT_IS_PRODUCTION',
                'true',
                config('payment.xendit.is_production') ? 'true' : 'false',
            );

            $checks[] = $this->check(
                str_starts_with((string) config('payment.xendit.secret_key'), 'xnd_production_'),
                'XENDIT_SECRET_KEY_MODE',
                'xnd_production_...',
                str_starts_with((string) config('payment.xendit.secret_key'), 'xnd_production_') ? 'produksi' : 'bukan produksi',
            );

            $checks[] = $this->check(
                (int) config('payment.xendit.session_duration_minutes', 0) >= 10,
                'XENDIT_SESSION_DURATION',
                '>= 10 menit',
                (string) config('payment.xendit.session_duration_minutes', 0).' menit',
            );
        }

        if ($gateway === 'midtrans') {
            $checks[] = $this->check(
                filled(config('payment.midtrans.server_key')),
                'MIDTRANS_SERVER_KEY',
                'terisi',
                filled(config('payment.midtrans.server_key')) ? 'terisi' : 'kosong',
            );

            $checks[] = $this->check(
                config('payment.midtrans.is_production') === true,
                'MIDTRANS_IS_PRODUCTION',
                'true',
                config('payment.midtrans.is_production') ? 'true' : 'false',
            );
        }

        $checks[] = $this->check(
            config('database.default') !== 'sqlite',
            'DB_CONNECTION',
            'mysql/pgsql',
            (string) config('database.default'),
            warningOnly: true,
        );

        $checks[] = $this->check(
            (int) config('ticketing.rate_limits.payment_webhook', 0) > 0,
            'PAYMENT_WEBHOOK_RATE_LIMIT',
            '> 0',
            (string) config('ticketing.rate_limits.payment_webhook'),
        );

        return $checks;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function check(
        bool $passes,
        string $label,
        string $expected,
        string $actual,
        bool $warningOnly = false,
    ): array {
        if ($passes) {
            return ['PASS', $label, 'OK'];
        }

        return [
            $warningOnly ? 'WARN' : 'FAIL',
            $label,
            sprintf('harus %s, saat ini %s', $expected, $actual),
        ];
    }

    private function logLevel(): string
    {
        return (string) (config('logging.channels.single.level')
            ?? config('logging.level')
            ?? 'debug');
    }
}
