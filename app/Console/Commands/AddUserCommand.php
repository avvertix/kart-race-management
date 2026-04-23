<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Fortify\CreateNewUserWithRole;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Jetstream;

class AddUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:add
                            {--email= : The user email address.}
                            {--password= : The user password.}
                            {--role= : The user\'s role. Default racemanager.}
                            {--name= : The user name to use. Default the email user.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register a new user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->option('email');
        $name = $this->option('name') ?? $this->getUsernameFrom($email);
        $password = $this->option('password');
        $role = $this->option('role') ?? 'racemanager';

        if (empty($password) && $this->input->isInteractive()) {
            $password = $this->secret('Please specify an 8 character password for the administrator');
        }

        $createUserAction = new CreateNewUserWithRole();

        try {
            $user = $createUserAction->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $password,
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                'role' => $role,
            ]);

            $this->line('');
            $this->line("User <comment>$email</comment> created.");
            $this->line('');

            return self::SUCCESS;
        } catch (ValidationException $ex) {
            if ($ex->validator->errors()->has('email') &&
               $ex->validator->errors()->first('email') === 'The email has already been taken.') {

                $this->line('');
                $this->error('User already existing');
                $this->line('');

                return self::INVALID;
            }

            $this->line('');
            $this->error('Validation errors');
            $this->line('');

            foreach ($ex->errors() as $key => $messages) {
                $this->comment($key);

                foreach ($messages as $message) {
                    $this->line("  - {$message}");
                }
                $this->line('');
            }

            return self::FAILURE;
        }

    }

    private function getUsernameFrom($email)
    {
        if (is_null($email)) {
            return null;
        }

        $et_offset = mb_strpos($email, '@');

        return $et_offset !== false ? mb_substr($email, 0, $et_offset) : $email;
    }
}
