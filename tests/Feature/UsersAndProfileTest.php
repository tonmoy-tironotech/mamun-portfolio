<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UsersAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function userData(User $user, array $overrides = []): array
    {
        return ['name' => $user->name, 'email' => $user->email, ...$overrides];
    }

    public function test_only_admins_can_reach_user_management(): void
    {
        $user = User::factory()->create();

        $this->get('/admin/users')->assertRedirect('/login');
        $this->put("/admin/users/{$user->id}", [])->assertRedirect('/login');

        $this->actingAs($user);
        $this->get('/admin/users')->assertRedirect('/student-corner');
        $this->put("/admin/users/{$user->id}", $this->userData($user, ['is_admin' => true]))->assertRedirect('/student-corner');

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_users_list_is_searched_and_paginated_without_secrets(): void
    {
        User::factory()->count(11)->create();
        User::factory()->create(['name' => 'Rahim Uddin', 'email' => 'zzz@example.com']);

        $this->actingAs($this->admin());

        $this->get('/admin/users')->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 10)
            ->where('users.total', 13)
            ->where('users.last_page', 2)
            ->missing('users.data.0.password')
            ->missing('users.data.0.remember_token'));

        $this->get('/admin/users?page=2')->assertInertia(fn (Assert $page) => $page->has('users.data', 3));
        $this->get('/admin/users?search=Rahim')->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('search', 'Rahim'));
        $this->get('/admin/users?search=zzz@')->assertInertia(fn (Assert $page) => $page->has('users.data', 1));
        $this->get('/admin/users?search=nobody-like-this')->assertInertia(fn (Assert $page) => $page->has('users.data', 0));
    }

    public function test_admin_can_edit_a_user_and_keep_the_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password-123']);

        $this->actingAs($this->admin());

        $this->get("/admin/users/{$user->id}/edit")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Form')
            ->where('user.email', $user->email)
            ->missing('user.password')
            ->where('user.is_admin', false));

        $this->put("/admin/users/{$user->id}", $this->userData($user, ['name' => 'New Name', 'email' => 'new@example.com', 'password' => '', 'password_confirmation' => '']))
            ->assertRedirect('/admin/users');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
        $this->assertTrue(Hash::check('old-password-123', $user->password));
    }

    public function test_admin_can_reset_a_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin())->put("/admin/users/{$user->id}", $this->userData($user, [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]))->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password', $user->password));
    }

    public function test_editing_never_changes_who_the_admin_is(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create();

        $this->actingAs($admin);

        $this->put("/admin/users/{$student->id}", $this->userData($student, ['is_admin' => true]))->assertSessionHasNoErrors();
        $this->put("/admin/users/{$admin->id}", $this->userData($admin, ['is_admin' => false, 'name' => 'Still Me']))->assertSessionHasNoErrors();

        $this->assertFalse($student->fresh()->is_admin);
        $this->assertTrue($admin->fresh()->is_admin);
        $this->assertSame('Still Me', $admin->fresh()->name);
    }

    public function test_make_admin_command_moves_the_role(): void
    {
        $admin = $this->admin();
        $student = User::factory()->create();

        $this->artisan('user:make-admin', ['email' => $student->email])->assertSuccessful();

        $this->assertTrue($student->fresh()->is_admin);
        $this->assertFalse($admin->fresh()->is_admin);
        $this->assertSame(1, User::where('is_admin', true)->count());
    }

    public function test_user_edit_is_validated(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($this->admin())->put("/admin/users/{$user->id}", [
            'name' => '',
            'email' => $taken->email,
            'password' => 'brand-new-password',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_student_profile_needs_a_login(): void
    {
        $this->get('/student-corner/profile')->assertRedirect('/login');
        $this->put('/student-corner/profile', [])->assertRedirect('/login');
        $this->put('/student-corner/profile/password', [])->assertRedirect('/login');
    }

    public function test_student_can_update_their_own_profile_only(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($student);

        $this->get('/student-corner/profile')->assertOk()->assertInertia(fn (Assert $page) => $page->component('StudentProfile'));

        $this->from('/student-corner/profile')
            ->put('/student-corner/profile', ['name' => 'Updated', 'email' => 'updated@example.com', 'is_admin' => true])
            ->assertRedirect('/verify-email');

        $student->refresh();
        $this->assertSame('Updated', $student->name);
        $this->assertSame('updated@example.com', $student->email);
        $this->assertFalse($student->is_admin);

        $this->put('/student-corner/profile', ['name' => 'Updated', 'email' => $other->email])->assertSessionHasErrors('email');
    }

    public function test_student_password_change_requires_the_current_password(): void
    {
        $student = User::factory()->create(['password' => 'old-password-123']);

        $this->actingAs($student);

        $this->put('/student-corner/profile/password', [
            'current_password' => 'wrong',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasErrors('current_password');

        $this->put('/student-corner/profile/password', [
            'current_password' => 'old-password-123',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password', $student->fresh()->password));
    }
}
