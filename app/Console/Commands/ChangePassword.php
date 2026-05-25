<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Command
{
    protected $signature = 'fambudget:change-password {email? : Email dell\'utente}';
    protected $description = 'Cambia la password di un utente FamBudget';

    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Email utente');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Nessun utente trovato con email: {$email}");
            return self::FAILURE;
        }

        $this->line("Utente trovato: {$user->name} ({$user->email})");

        $password = $this->secret('Nuova password');
        $confirm  = $this->secret('Conferma nuova password');

        if ($password !== $confirm) {
            $this->error('Le password non coincidono.');
            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('La password deve essere di almeno 8 caratteri.');
            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);

        $this->info("Password aggiornata per {$user->email}.");
        return self::SUCCESS;
    }
}
