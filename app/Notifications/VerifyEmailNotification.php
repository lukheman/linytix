<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Email verifikasi dikirim lewat queue agar registrasi tidak menunggu SMTP.
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;
}
