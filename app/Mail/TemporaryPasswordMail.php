<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $temporaryPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $temporaryPassword)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this
            ->subject('Your Temporary Password')
            ->view('mail.temporary-password')
            ->with([
                'name' => $this->user->name,
                'email' => $this->user->email,
                'temporaryPassword' => $this->temporaryPassword,
                'loginUrl' => url('/login'),
            ]);
    }
}
