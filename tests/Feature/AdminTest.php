<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/Login'));
    }

    public function test_superadmin_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        $this->get('/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('auth.user.email', $user->email)
                ->missing('auth.user.password'));

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret-password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logged_in_user_skips_login_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/login')
            ->assertRedirect('/admin');
    }

    public function test_profile_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/admin/profile', ['name' => 'New Name', 'email' => 'new@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('new@example.com', $user->fresh()->email);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

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

    public function test_there_is_no_registration(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_seeder_creates_a_single_superadmin_once(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::count());
    }
}
