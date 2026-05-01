<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a super admin user for the dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('What is the admin name?');
        $email = $this->ask('What is the admin email?');
        $password = $this->secret('What is the admin password? (minimum 6 characters)');

        if (\App\Models\User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists!');
            return 1;
        }

        if (strlen($password) < 6) {
            $this->error('Password must be at least 6 characters long.');
            return 1;
        }

        $user = \App\Models\User::create([
            'name' => $name,
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make($password),
            'role' => 'admin',
        ]);

        $this->info("Super admin account created successfully!");
        $this->info("Email: {$email}");
        $this->info("Role: {$user->role}");
        
        return 0;
    }
}
