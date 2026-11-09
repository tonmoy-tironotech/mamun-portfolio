<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/profile')->assertRedirect('/login');
        $this->get('/admin/projects')->assertRedirect('/login');
        $this->get('/student-corner')->assertRedirect('/login');
    }

    public function test_login_and_register_pages_render(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
    }

    public function test_student_corner_needs_a_login_and_the_old_url_redirects(): void
    {
        $this->get('/student-corner')->assertRedirect('/login');
        $this->get('/student')->assertStatus(301)->assertRedirect('/student-corner');

        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect('/student-corner');
        $this->get('/student-corner')->assertOk();
    }

    public function test_login_landing_page_depends_on_the_user(): void
    {
        $admin = User::factory()->create(['password' => 'secret-password', 'is_admin' => true]);
        $student = User::factory()->create(['password' => 'secret-password']);

        // An admin who first tried a student page still lands in the admin panel
        $this->get('/student-corner');
        $this->post('/login', ['email' => $admin->email, 'password' => 'secret-password'])->assertRedirect('/admin');
        $this->post('/logout');

        // An admin whose session ended inside the panel returns to that page
        $this->get('/admin/projects');
        $this->post('/login', ['email' => $admin->email, 'password' => 'secret-password'])->assertRedirect('/admin/projects');
        $this->post('/logout');

        // A student returns to the student page they asked for, but never to a raw file
        $this->get('/student-corner?search=abc');
        $this->post('/login', ['email' => $student->email, 'password' => 'secret-password'])->assertRedirect('/student-corner?search=abc');
        $this->post('/logout');

        $this->get('/student-corner/documents/1/download');
        $this->post('/login', ['email' => $student->email, 'password' => 'secret-password'])->assertRedirect('/student-corner');
    }

    public function test_old_admin_login_url_redirects(): void
    {
        $this->get('/admin/login')->assertStatus(301)->assertRedirect('/login');
    }

    public function test_admin_logs_in_to_the_admin_panel(): void
    {
        $user = User::factory()->create(['password' => 'secret-password', 'is_admin' => true]);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        $this->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('auth.user.email', $user->email)
                ->missing('auth.user.password'));

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_student_logs_in_to_the_student_page(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect('/student-corner');

        $this->get('/student-corner')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student')->where('auth.user.is_admin', false));

        $this->get('/admin')->assertRedirect('/student-corner');
        $this->get('/admin/profile')->assertRedirect('/student-corner');

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inertia_login_forces_a_full_page_load_for_admins(): void
    {
        $user = User::factory()->create(['password' => 'secret-password', 'is_admin' => true]);

        $this->withHeaders(['X-Inertia' => 'true'])
            ->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', url('/admin'));
    }

    public function test_sign_up_cannot_grant_admin_access(): void
    {
        $this->post('/register', [
            'student_id' => 'S-1001',
            'education_level' => 'Bachelor',
            'mobile' => '01712345678',
            'name' => 'A Student',
            'email' => 'student@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
            'is_admin' => true,
        ])->assertRedirect('/verify-email');

        $this->assertFalse(User::firstWhere('email', 'student@example.com')->is_admin);
    }

    public function test_student_can_sign_up(): void
    {
        $this->post('/register', [
            'student_id' => 'S-1001',
            'education_level' => 'Bachelor',
            'mobile' => '01712345678',
            'name' => 'A Student',
            'email' => 'student@example.com',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertRedirect('/verify-email');

        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('brand-new-password', User::firstWhere('email', 'student@example.com')->password));
        $this->get('/verify-email')->assertOk();
    }

    public function test_sign_up_validates_input(): void
    {
        $existing = User::factory()->create();

        $this->post('/register', [
            'student_id' => 'S-1001',
            'education_level' => 'Bachelor',
            'mobile' => '01712345678',
            'name' => 'A Student',
            'email' => $existing->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['email', 'password']);

        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logged_in_users_skip_login_and_register(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get('/login')->assertRedirect('/admin');
        $this->get('/register')->assertRedirect('/admin');

        $this->actingAs(User::factory()->create());
        $this->get('/login')->assertRedirect('/student-corner');
        $this->get('/register')->assertRedirect('/student-corner');
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create(['is_admin' => true]);

        $this->actingAs($user)
            ->put('/admin/profile', ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-password', 'is_admin' => true]);

        $this->actingAs($user)
            ->put('/admin/profile/password', [
                'current_password' => 'wrong',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($user)
            ->put('/admin/profile/password', [
                'current_password' => 'secret-password',
                'password' => 'brand-new-password',
                'password_confirmation' => 'brand-new-password',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password', $user->fresh()->password));
    }

    public function test_seeder_keeps_existing_users_projects_and_documents(): void
    {
        Storage::fake('local');

        $this->seed();
        $this->seed();

        $this->assertSame(1, User::count());
        $this->assertTrue(User::first()->is_admin);
        $this->assertDatabaseCount('projects', 7);
        $this->assertDatabaseCount('documents', 3);
        $this->assertDatabaseCount('publications', 10);

        foreach (Document::all() as $document) {
            Storage::disk('local')->assertExists($document->file_path);
        }
    }

    public function test_make_admin_command(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:make-admin', ['email' => $user->email])->assertSuccessful();

        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_password_reset_command_targets_the_given_user(): void
    {
        $first = User::factory()->create(['password' => 'first-password']);
        $second = User::factory()->create(['password' => 'second-password']);

        $this->artisan('user:reset-password', ['email' => $second->email])->assertSuccessful();

        $this->assertTrue(Hash::check('first-password', $first->fresh()->password));
        $this->assertFalse(Hash::check('second-password', $second->fresh()->password));
    }
}
