<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TicketingMailTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticketing:mail-test {email : Alamat tujuan email uji}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email uji untuk memverifikasi konfigurasi mailer (SMTP/API)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = (string) $this->argument('email');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error('Alamat email tidak valid: '.$recipient);

            return self::FAILURE;
        }

        $mailer = (string) config('mail.default');

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn(sprintf(
                'MAIL_MAILER=%s tidak mengirim email nyata. Ubah ke smtp sebelum menguji pengiriman.',
                $mailer,
            ));
        }

        $this->line(sprintf('Mengirim email uji ke %s melalui mailer "%s"...', $recipient, $mailer));

        try {
            Mail::raw(
                implode("\n\n", [
                    'Ini email uji dari LinyTix.',
                    'Jika Anda menerima email ini, konfigurasi mailer sudah benar.',
                    'Dikirim pada: '.now()->translatedFormat('l, d F Y H.i').' WIB',
                ]),
                function (Message $message) use ($recipient): void {
                    $message->to($recipient)->subject('Tes email LinyTix');
                },
            );
        } catch (Throwable $exception) {
            $this->error('Gagal mengirim email: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Email uji selesai diproses. Periksa inbox '.$recipient.'.');

        return self::SUCCESS;
    }
}
