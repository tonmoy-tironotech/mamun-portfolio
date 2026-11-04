<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:reset-password {email}', function (string $email) {
    $user = User::where('email', $email)->firstOrFail();
    $password = Str::password(16, symbols: false);

    $user->update(['password' => $password]);

    $this->warn("New password for {$user->email}: {$password}");
})->purpose('Generate a new password for a user');

Artisan::command('user:make-admin {email}', function (string $email) {
    $user = User::where('email', $email)->firstOrFail();

    // There is only ever one admin, so this moves the role
    DB::transaction(function () use ($user) {
        User::where('is_admin', true)->update(['is_admin' => false]);
        $user->forceFill(['is_admin' => true])->save();
    });

    $this->info("{$email} is now the admin.");
})->purpose('Move the admin role to a user');
