<?php

namespace Tests\Feature;

use App\Mail\ContactReplySent;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactReplyTest extends TestCase
{
    use RefreshDatabase;

    private ContactMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Mail::fake();

        $this->message = ContactMessage::create([
            'name' => 'Ayesha',
            'email' => 'ayesha@example.com',
            'subject' => 'Admission',
            'message' => 'A question',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true, 'email' => 'boss@example.com']);
    }

    public function test_admin_reply_is_saved_and_queued_to_the_visitor(): void
    {
        $this->actingAs($this->admin());

        $this->from("/admin/messages/{$this->message->id}")
            ->post("/admin/messages/{$this->message->id}/reply", ['body' => 'Here is the answer'])
            ->assertRedirect("/admin/messages/{$this->message->id}")
            ->assertSessionHas('status');

        $this->assertDatabaseHas('contact_message_replies', ['contact_message_id' => $this->message->id, 'body' => 'Here is the answer']);

        Mail::assertQueued(ContactReplySent::class, fn ($mail) => $mail->hasTo('ayesha@example.com')
            && $mail->hasReplyTo('boss@example.com')
            && $mail->hasSubject('Re: Admission'));
        Mail::assertNothingSent();
    }

    public function test_replies_show_in_the_thread_and_status(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/messages')->assertInertia(fn (Assert $page) => $page->where('messages.data.0.is_replied', false));

        $this->post("/admin/messages/{$this->message->id}/reply", ['body' => 'First']);
        $this->post("/admin/messages/{$this->message->id}/reply", ['body' => 'Second']);

        $this->get("/admin/messages/{$this->message->id}")->assertInertia(fn (Assert $page) => $page
            ->has('replies', 2)
            ->where('replies.0.body', 'First')
            ->where('replies.1.body', 'Second'));

        $this->get('/admin/messages')->assertInertia(fn (Assert $page) => $page->where('messages.data.0.is_replied', true));
    }

    public function test_reply_must_have_text(): void
    {
        $this->actingAs($this->admin());

        $this->post("/admin/messages/{$this->message->id}/reply", ['body' => ''])->assertSessionHasErrors('body');

        $this->assertDatabaseCount('contact_message_replies', 0);
        Mail::assertNothingQueued();
    }

    public function test_only_admins_can_reply(): void
    {
        $this->post("/admin/messages/{$this->message->id}/reply", ['body' => 'x'])->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->post("/admin/messages/{$this->message->id}/reply", ['body' => 'x'])
            ->assertRedirect('/student-corner');

        $this->assertDatabaseCount('contact_message_replies', 0);
        Mail::assertNothingQueued();
    }

    public function test_reply_mail_escapes_text_and_quotes_the_original(): void
    {
        $reply = $this->message->replies()->create(['body' => "<script>alert(1)</script>\nSecond line"]);

        $html = (new ContactReplySent($reply))->render();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('A question', $html);
    }

    public function test_deleting_a_message_removes_its_replies(): void
    {
        $this->message->replies()->create(['body' => 'Answer']);

        $this->actingAs($this->admin())->delete("/admin/messages/{$this->message->id}");

        $this->assertSame(0, ContactMessageReply::count());
    }
}
