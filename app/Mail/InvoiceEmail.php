<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
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
        $mail = $this->from(
                    env('MAIL_FROM_ADDRESS'),
                    env('MAIL_FROM_NAME')
                )
                ->subject($this->data['subject'])
                ->view('email.invoice_email')
                ->with($this->data);

        // Attach files if exist
        if (!empty($this->data['files'])) {
            foreach ($this->data['files'] as $file) {
                $mail->attach($file);
            }
        }

        return $mail;
    }
}
