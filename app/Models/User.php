<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\EmailVerificationCode;
use App\Notifications\QueuedResetPassword;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token', 'email_verification_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const VERIFICATION_CODE_MINUTES = 15;

    public const VERIFICATION_RESEND_SECONDS = 60;

    public const VERIFICATION_MAX_ATTEMPTS = 5;

    public function isApproved(): bool
    {
        return $this->is_admin || $this->approved_at !== null;
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->is_admin || $this->email_verified_at !== null;
    }

    /**
     * Replace any earlier code with a new one and queue it. Only a hash is stored.
     */
    public function sendEmailVerificationCode(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(self::VERIFICATION_CODE_MINUTES),
            'email_verification_sent_at' => now(),
            'email_verification_attempts' => 0,
        ])->save();

        $this->notify(new EmailVerificationCode($code));
    }

    public function confirmEmailWithCode(string $code): bool
    {
        if (
            $this->email_verification_code === null
            || $this->email_verification_expires_at?->isPast() !== false
            || $this->email_verification_attempts >= self::VERIFICATION_MAX_ATTEMPTS
        ) {
            return false;
        }

        if (! Hash::check($code, $this->email_verification_code)) {
            $this->forceFill(['email_verification_attempts' => $this->email_verification_attempts + 1])->save();

            return false;
        }

        $this->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
            'email_verification_attempts' => 0,
        ])->save();

        return true;
    }

    /**
     * Seconds until another code can be sent.
     */
    public function verificationCodeCooldown(): int
    {
        if ($this->email_verification_sent_at === null) {
            return 0;
        }

        $availableAt = $this->email_verification_sent_at->copy()->addSeconds(self::VERIFICATION_RESEND_SECONDS);

        return max((int) ceil(now()->diffInSeconds($availableAt, false)), 0);
    }

    /**
     * Students who verified their email and are waiting for an admin.
     *
     * @param  Builder<User>  $query
     */
    public function scopePending(Builder $query): void
    {
        $query->where('is_admin', false)->whereNotNull('email_verified_at')->whereNull('approved_at');
    }

    /**
     * Send the password reset link through the queue.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'approved_at' => 'datetime',
            'email_verification_expires_at' => 'datetime',
            'email_verification_sent_at' => 'datetime',
        ];
    }
}
