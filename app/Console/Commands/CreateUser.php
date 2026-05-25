<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    protected $signature = 'fambudget:create-user';
    protected $description = 'Crea un nuovo utente FamBudget';

    public function handle(): int
    {
        $name = $this->ask('Nome');

        $email = $this->ask('Email');
        if (User::where('email', $email)->exists()) {
            $this->error("Esiste già un utente con email: {$email}");
            return self::FAILURE;
        }

        $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
        if ($validator->fails()) {
            $this->error('Email non valida.');
            return self::FAILURE;
        }

        $password = $this->secret('Password');
        $confirm  = $this->secret('Conferma password');

        if ($password !== $confirm) {
            $this->error('Le password non coincidono.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('La password deve essere di almeno 8 caratteri.');
            return self::FAILURE;
        }

        User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("Utente '{$name}' ({$email}) creato con successo.");
        return self::SUCCESS;
    }
}
