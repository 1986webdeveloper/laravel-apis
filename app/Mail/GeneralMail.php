<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneralMail extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mail_arr)
    {
        $this->data = $mail_arr ? $mail_arr : [];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->data['subject'] ? $this->data['subject'] : '';
        $view = $this->data['template_name'] ? $this->data['template_name'] : '';
        $from_name = $this->data['from_name'] ? $this->data['from_name'] : '';
        $from_email = $this->data['from_email'] ? $this->data['from_email'] : '';


        return $this->view($view)
            ->subject($subject)
            ->from($from_email, $from_name)
            ->with($this->data);
    }
}
