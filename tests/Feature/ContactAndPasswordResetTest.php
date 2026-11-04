<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactAndPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'email' => 'boss@example.com']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function contactData(array $overrides = []): array
    {
        return ['name' => 'Ayesha', 'email' => 'ayesha@example.com', 'subject' => 'Hello', 'message' => 'A question', ...$overrides];
    }

    public function test_contact_form_saves_the_message_and_queues_mail_to_the_admin(): void
    {
        Mail::fake();
        $this->admin();
        User::factory()->create();

        $this->from('/contact')->post('/contact', $this->contactData())
            ->assertRedirect('/contact')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('contact_messages', ['email' => 'ayesha@example.com', 'read_at' => null]);

        Mail::assertQueued(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('boss@example.com')
            && $mail->hasReplyTo('ayesha@example.com')
            && $mail->hasSubject('New contact message: Hello'));
        Mail::assertNothingSent();
    }

    public function test_message_is_kept_even_without_an_admin_to_mail(): void
    {
        Mail::fake();

        $this->post('/contact', $this->contactData())->assertSessionHas('status');

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingQueued();
    }

    public function test_mail_escapes_what_the_visitor_typed(): void
    {
        $message = ContactMessage::create($this->contactData(['message' => "<script>alert(1)</script>\nline two"]));

        $html = (new ContactMessageReceived($message))->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('<br', $html);
    }

    public function test_contact_form_is_validated(): void
    {
        Mail::fake();

        $this->post('/contact', ['email' => 'not-an-email'])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingQueued();
    }

    public function test_bots_filling_the_hidden_field_are_ignored_quietly(): void
    {
        Mail::fake();
        $this->admin();

        $this->post('/contact', $this->contactData(['website' => 'spam.example']))->assertSessionHas('status');

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingQueued();
    }

    public function test_contact_form_is_rate_limited(): void
    {
        Mail::fake();

        foreach (range(1, 5) as $attempt) {
            $this->post('/contact', $this->contactData())->assertRedirect();
        }

        $this->post('/contact', $this->contactData())->assertStatus(429);
        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_only_admins_can_read_messages(): void
    {
        $message = ContactMessage::create($this->contactData());

        $this->get('/admin/messages')->assertRedirect('/login');
        $this->get("/admin/messages/{$message->id}")->assertRedirect('/login');

        $this->actingAs(User::factory()->create());
        $this->get('/admin/messages')->assertRedirect('/student-corner');
        $this->get("/admin/messages/{$message->id}")->assertRedirect('/student-corner');
        $this->delete("/admin/messages/{$message->id}")->assertRedirect('/student-corner');

        $this->assertNull($message->fresh()->read_at);
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_admin_can_search_paginate_read_and_delete_messages(): void
    {
        foreach (range(1, 11) as $number) {
            ContactMessage::create($this->contactData(['subject' => "Subject {$number}"]));
        }
        $target = ContactMessage::create($this->contactData(['name' => 'Karim', 'subject' => 'Admission query']));

        $this->actingAs($this->admin());

        $this->get('/admin/messages')->assertInertia(fn (Assert $page) => $page
            ->component('admin/messages/Index')
            ->has('messages.data', 10)
            ->where('messages.total', 12)
            ->where('messages.last_page', 2)
            ->where('messages.data.0.is_read', false)
            ->where('unreadMessages', 12));

        $this->get('/admin/messages?page=2')->assertInertia(fn (Assert $page) => $page->has('messages.data', 2));
        $this->get('/admin/messages?search=Admission')->assertInertia(fn (Assert $page) => $page->has('messages.data', 1));
        $this->get('/admin/messages?search=Karim')->assertInertia(fn (Assert $page) => $page->has('messages.data', 1));
        $this->get('/admin/messages?search=nothing-here')->assertInertia(fn (Assert $page) => $page->has('messages.data', 0));

        $this->get("/admin/messages/{$target->id}")->assertInertia(fn (Assert $page) => $page
            ->component('admin/messages/Show')
            ->where('contactMessage.subject', 'Admission query')
            ->where('unreadMessages', 11));
        $this->assertNotNull($target->fresh()->read_at);

        $this->delete("/admin/messages/{$target->id}")->assertRedirect('/admin/messages');
        $this->assertDatabaseMissing('contact_messages', ['id' => $target->id]);
    }

    public function test_unread_count_is_only_shared_in_the_admin_panel(): void
    {
        ContactMessage::create($this->contactData());

        $this->get('/contact')->assertInertia(fn (Assert $page) => $page->where('unreadMessages', null));
    }

    public function test_forgot_password_queues_a_link_without_revealing_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->get('/forgot-password')->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/ForgotPassword'));

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame(session('status'), $known->getSession()->get('status'));
        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors()->assertSessionHas('status', 'If that email has an account, a reset link is on its way.');

        Notification::assertSentTo($user, QueuedResetPassword::class);
        Notification::assertSentTimes(QueuedResetPassword::class, 1);
        $this->assertInstanceOf(ShouldQueue::class, new QueuedResetPassword('token'));
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);
        $token = Password::createToken($user);

        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('auth/ResetPassword')
            ->where('token', $token)
            ->where('email', $user->email));

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect('/login')->assertSessionHas('status');

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
        $this->assertGuest();

        // A link only works once
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'another-password-1',
            'password_confirmation' => 'another-password-1',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_reset_rejects_a_bad_token_and_weak_input(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);

        $this->post('/reset-password', [
            'token' => 'wrong-token',
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->post('/reset-password', [
            'token' => Password::createToken($user),
            'email' => $user->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_logged_in_users_skip_the_forgot_password_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/forgot-password')->assertRedirect('/student-corner');
    }
}
