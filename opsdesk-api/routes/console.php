<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('ops:promote-admin {email}', function (string $email): int {
    $user = User::query()->where('email', $email)->first();

    if (! $user) {
        $this->error('User not found. Register the account via the API first.');
        return 1;
    }

    $user->forceFill(['role' => 'admin'])->save();
    $user->tokens()->delete(); // Revoke prior viewer tokens when privileges change.
    $this->info('Account promoted to admin. Log in again to obtain a new token.');
    return 0;
})->purpose('Promote an existing user to admin from the trusted CLI only');
