<?php

namespace App\Console\Commands;

use App\Enums\RolesEnum;
use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use function Laravel\Prompts\form;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $roleOptions = [];
        foreach (RolesEnum::cases() as $roleEnum) {
            $roleOptions[$roleEnum->value] = $roleEnum->label();
        }

        $responses = form()
            ->text('What is your name?', required: true, name: 'name')
            ->text('What is your email?', required: true, name: 'email')
            ->select(
                label: 'What roles should be assigned?',
                options: $roleOptions,
                name: 'roles'
            )
            ->confirm('Confirm?')
            ->submit();

        $tempPassword = Str::random(12);

        $user = User::create([
            'name' => $responses['name'],
            'email' => $responses['email'],
            'password' => $tempPassword,
        ]);

        if (! empty($responses['roles'])) {
            $user->assignRole($responses['roles']);
        }

        Mail::to($user->email)->send(new UserCreated($user, $tempPassword));
    }
}
