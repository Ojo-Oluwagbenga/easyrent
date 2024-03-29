<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailNotify extends Mailable
{
    use Queueable, SerializesModels;
    private $data = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        //
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Mail Notify',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    // public function content()
    // {
    //     return new Content(
    //         view: 'view.index',
    //     );
    //     // return$this->from('ojojohn2907@gmail.com', 'The Ojo JOHN')
    //     // ->subject($this->data['subject'])->view('emails.index')->with('data', $this->data);
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    public function build(){
        if ($this->data['type'] == 'mail_confirm'){
            return $this->from('myeasyrentonline@gmail.com', "Easy Rent Online")
            ->subject($this->data['subject'])->view('emails.index')->with('data', $this->data);
        }
        if ($this->data['type'] == 'forgot_password'){
            return $this->from('myeasyrentonline@gmail.com', "Easy Rent Online")
            ->subject($this->data['subject'])->view('emails.forgotpassword')->with('data', $this->data);
        }
    }
}
