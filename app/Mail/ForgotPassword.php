<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from(
                env('MAIL_FROM_ADDRESS'),
                env('MAIL_FROM_NAME')
            )
            ->subject($this->data['subject'])
            ->view('email.forgot_password')
            ->with([
                'data' => $this->data
            ]);
    }
}
