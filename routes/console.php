<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('superadmin:reset-password', function () {
    $user = User::firstOrFail();
    $password = Str::password(16, symbols: false);

    $user->update(['password' => $password]);

    $this->warn("New password for {$user->email}: {$password}");
})->purpose('Generate a new password for the superadmin');
