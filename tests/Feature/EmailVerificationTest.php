<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Notification::fake();
    }

    private function signUp(): User
    {
        $this->post('/register', [
            'name' => 'A Student',
            'email' => 'student@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect('/verify-email');

        return User::firstWhere('email', 'student@example.com');
    }

    private function sentCode(User $user): string
    {
        $code = null;

        Notification::assertSentTo($user, EmailVerificationCode::class, function (EmailVerificationCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }

    private function unverifiedStudent(): User
    {
        $user = User::factory()->unverified()->pending()->create();
        $user->sendEmailVerificationCode();

        return $user->fresh();
    }

    public function test_sign_up_queues_a_six_digit_code_and_stores_it_hashed(): void
    {
        $user = $this->signUp();
        $code = $this->sentCode($user);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $user->email_verification_code);
        $this->assertNull($user->email_verified_at);
        $this->assertInstanceOf(ShouldQueue::class, new EmailVerificationCode('123456'));
    }

    public function test_code_page_never_exposes_the_code(): void
    {
        $this->signUp();

        $this->get('/verify-email')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('auth/VerifyEmail')
            ->where('email', 'student@example.com')
            ->missing('auth.user.email_verification_code')
            ->missing('code'));
    }

    public function test_unverified_student_cannot_reach_student_corner(): void
    {
        $document = Document::create([
            'title' => 'Notes',
            'short_description' => 'Short',
            'long_description' => 'Long',
            'file_path' => 'documents/notes.pdf',
            'file_name' => 'notes.pdf',
            'file_size' => 13,
        ]);

        $this->actingAs($this->unverifiedStudent());

        $this->get('/student-corner')->assertRedirect('/verify-email');
        $this->get("/student-corner/documents/{$document->id}")->assertRedirect('/verify-email');
        $this->get("/student-corner/documents/{$document->id}/download")->assertRedirect('/verify-email');
        $this->get('/student-corner/profile')->assertOk();
    }

    public function test_correct_code_verifies_and_leaves_the_account_pending(): void
    {
        $user = $this->signUp();

        $this->post('/verify-email', ['code' => $this->sentCode($user)])
            ->assertRedirect('/student-corner')
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_code);
        $this->assertNull($user->approved_at);
        $this->assertFalse($user->isApproved());

        $this->get('/student-corner')->assertOk()->assertInertia(fn (Assert $page) => $page->where('documents', null));
        $this->get('/verify-email')->assertRedirect('/student-corner');
    }

    public function test_wrong_code_is_rejected(): void
    {
        $user = $this->signUp();
        $code = $this->sentCode($user);
        $wrong = $code === '000000' ? '000001' : '000000';

        $this->post('/verify-email', ['code' => $wrong])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_code_stops_working_after_five_wrong_attempts(): void
    {
        $user = $this->signUp();
        $code = $this->sentCode($user);
        $wrong = $code === '000000' ? '000001' : '000000';

        foreach (range(1, 5) as $attempt) {
            $this->post('/verify-email', ['code' => $wrong])->assertSessionHasErrors('code');
        }

        $this->post('/verify-email', ['code' => $code])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->signUp();
        $code = $this->sentCode($user);

        $this->travel(User::VERIFICATION_CODE_MINUTES + 1)->minutes();

        $this->post('/verify-email', ['code' => $code])->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_waits_for_the_cooldown_and_replaces_the_old_code(): void
    {
        $user = $this->signUp();
        $oldCode = $this->sentCode($user);

        $this->from('/verify-email')->post('/verify-email/resend')->assertRedirect('/verify-email');
        Notification::assertSentToTimes($user, EmailVerificationCode::class, 1);

        $this->get('/verify-email')->assertInertia(fn (Assert $page) => $page->where('resendIn', fn ($seconds) => $seconds > 0));

        $this->travel(User::VERIFICATION_RESEND_SECONDS + 1)->seconds();

        $this->from('/verify-email')->post('/verify-email/resend')->assertSessionHas('status');
        Notification::assertSentToTimes($user, EmailVerificationCode::class, 2);

        $newCode = null;
        Notification::assertSentTo($user, EmailVerificationCode::class, function (EmailVerificationCode $notification) use (&$newCode) {
            $newCode = $notification->code;

            return true;
        });

        if ($newCode !== $oldCode) {
            $this->post('/verify-email', ['code' => $oldCode])->assertSessionHasErrors('code');
        }

        $this->post('/verify-email', ['code' => $newCode])->assertRedirect('/student-corner');
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_guests_cannot_use_the_code_routes(): void
    {
        $this->get('/verify-email')->assertRedirect('/login');
        $this->post('/verify-email', ['code' => '123456'])->assertRedirect('/login');
        $this->post('/verify-email/resend')->assertRedirect('/login');
    }

    public function test_changing_email_requires_verifying_the_new_address(): void
    {
        $student = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($student)
            ->put('/student-corner/profile', ['name' => $student->name, 'email' => 'new@example.com'])
            ->assertRedirect('/verify-email');

        $student->refresh();
        $this->assertSame('new@example.com', $student->email);
        $this->assertNull($student->email_verified_at);
        Notification::assertSentTo($student, EmailVerificationCode::class);

        $this->get('/student-corner')->assertRedirect('/verify-email');
    }

    public function test_changing_only_the_name_keeps_the_email_verified(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->from('/student-corner/profile')
            ->put('/student-corner/profile', ['name' => 'New Name', 'email' => $student->email])
            ->assertRedirect('/student-corner/profile');

        $this->assertNotNull($student->fresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_admin_never_needs_a_code(): void
    {
        $admin = User::factory()->unverified()->create(['is_admin' => true]);

        $this->actingAs($admin);

        $this->get('/student-corner')->assertOk();

        $this->put('/admin/profile', ['name' => $admin->name, 'email' => 'boss@example.com'])->assertSessionHasNoErrors();
        Notification::assertNothingSent();
    }

    public function test_admin_cannot_approve_an_unverified_student(): void
    {
        $student = $this->unverifiedStudent();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->post("/admin/users/{$student->id}/approve")->assertSessionHas('status');
        $this->assertNull($student->fresh()->approved_at);

        $this->get('/admin/users')->assertInertia(fn (Assert $page) => $page
            ->where('pendingUsers', 0)
            ->where('users.data.1.id', $student->id)
            ->where('users.data.1.is_unverified', true)
            ->where('users.data.1.is_pending', false));
    }
}
