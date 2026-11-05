<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    private function document(): Document
    {
        Storage::disk('local')->put('documents/notes.pdf', 'file contents');

        return Document::create([
            'title' => 'Secret notes',
            'short_description' => 'Short',
            'long_description' => 'Long',
            'file_path' => 'documents/notes.pdf',
            'file_name' => 'notes.pdf',
            'file_size' => 13,
        ]);
    }

    public function test_new_sign_up_is_pending_and_cannot_approve_itself(): void
    {
        $this->post('/register', [
            'name' => 'A Student',
            'email' => 'student@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
            'approved_at' => now()->toDateTimeString(),
        ])->assertRedirect('/verify-email');

        $this->assertNull(User::firstWhere('email', 'student@example.com')->approved_at);
    }

    public function test_pending_student_cannot_reach_any_document(): void
    {
        $document = $this->document();

        $this->actingAs(User::factory()->pending()->create());

        $this->get('/student-corner')->assertInertia(fn (Assert $page) => $page->where('documents', null));
        $this->get("/student-corner/documents/{$document->id}")->assertRedirect('/student-corner');
        $this->get("/student-corner/documents/{$document->id}/download")->assertRedirect('/student-corner');
    }

    public function test_pending_student_can_still_use_their_profile(): void
    {
        $this->actingAs(User::factory()->pending()->create());

        $this->get('/student-corner/profile')->assertOk();
    }

    public function test_approving_gives_access_and_revoking_takes_it_away(): void
    {
        $document = $this->document();
        $student = User::factory()->pending()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post("/admin/users/{$student->id}/approve")->assertSessionHas('status');
        $this->assertTrue($student->fresh()->isApproved());

        $this->actingAs($student->fresh());
        $this->get('/student-corner')->assertInertia(fn (Assert $page) => $page->has('documents.data', 1));
        $this->get("/student-corner/documents/{$document->id}")->assertOk();
        $this->get("/student-corner/documents/{$document->id}/download")->assertOk();

        $this->actingAs($admin)->delete("/admin/users/{$student->id}/approve")->assertSessionHas('status');
        $this->assertFalse($student->fresh()->isApproved());

        $this->actingAs($student->fresh())
            ->get("/student-corner/documents/{$document->id}")
            ->assertRedirect('/student-corner');
    }

    public function test_only_admins_can_approve(): void
    {
        $pending = User::factory()->pending()->create();

        $this->post("/admin/users/{$pending->id}/approve")->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->post("/admin/users/{$pending->id}/approve")
            ->assertRedirect('/student-corner');
        $this->actingAs($pending)
            ->post("/admin/users/{$pending->id}/approve")
            ->assertRedirect('/student-corner');

        $this->assertNull($pending->fresh()->approved_at);
    }

    public function test_admin_list_shows_pending_students_first_with_a_count(): void
    {
        User::factory()->count(2)->create();
        $pending = User::factory()->pending()->create(['name' => 'Waiting Student']);
        User::factory()->create();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/users')
            ->assertInertia(fn (Assert $page) => $page
                ->where('users.data.0.id', $pending->id)
                ->where('users.data.0.is_pending', true)
                ->where('users.data.1.is_pending', false)
                ->where('pendingUsers', 1));
    }

    public function test_admin_is_never_pending(): void
    {
        $document = $this->document();

        $this->actingAs(User::factory()->pending()->create(['is_admin' => true]))
            ->get("/student-corner/documents/{$document->id}")
            ->assertOk();
    }

    public function test_admin_can_reject_a_pending_student(): void
    {
        $document = $this->document();
        $student = User::factory()->pending()->create();

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->post("/admin/users/{$student->id}/reject")->assertSessionHas('status');
        $this->assertNotNull($student->fresh()->rejected_at);

        $this->get('/admin/users')->assertInertia(fn (Assert $page) => $page
            ->where('pendingUsers', 0)
            ->where('users.data.1.is_rejected', true)
            ->where('users.data.1.is_pending', false));

        $this->actingAs($student->fresh());
        $this->get('/student-corner')->assertInertia(fn (Assert $page) => $page
            ->where('documents', null)
            ->where('isRejected', true));
        $this->get("/student-corner/documents/{$document->id}")->assertRedirect('/student-corner');
        $this->get("/student-corner/documents/{$document->id}/download")->assertRedirect('/student-corner');
    }

    public function test_rejected_student_can_be_approved_later(): void
    {
        $student = User::factory()->rejected()->create();

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post("/admin/users/{$student->id}/approve")
            ->assertSessionHas('status');

        $student->refresh();
        $this->assertNull($student->rejected_at);
        $this->assertTrue($student->isApproved());

        $this->actingAs($student)->get('/student-corner')->assertInertia(fn (Assert $page) => $page
            ->where('isRejected', false)
            ->has('documents.data', 0));
    }

    public function test_only_admins_can_reject_and_the_admin_cannot_be_rejected(): void
    {
        $pending = User::factory()->pending()->create();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->post("/admin/users/{$pending->id}/reject")->assertRedirect('/login');
        $this->actingAs(User::factory()->create())
            ->post("/admin/users/{$pending->id}/reject")
            ->assertRedirect('/student-corner');
        $this->assertNull($pending->fresh()->rejected_at);

        $this->actingAs($admin)->post("/admin/users/{$admin->id}/reject")->assertSessionHas('status');
        $this->assertNull($admin->fresh()->rejected_at);
    }
}
