<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $fullname;
    public $otp;

    public function __construct($fullname, $otp)
    {
        $this->fullname = $fullname;
        $this->otp      = $otp;
    }

    public function build()
    {
        return $this->subject('Kode OTP Reset Password - Bintang Vet Clinic')
                    ->view('emails.otp-reset-password');
    }
}
