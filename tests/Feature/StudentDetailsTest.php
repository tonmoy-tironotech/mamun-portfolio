<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StudentDetailsTest extends TestCase
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
    private function signUpData(array $overrides = []): array
    {
        return [
            'name' => 'A Student',
            'email' => 'student@example.com',
            'student_id' => 'S-2024-01',
            'education_level' => 'B.Sc 3rd year',
            'mobile' => '01712345678',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
            ...$overrides,
        ];
    }

    public function test_sign_up_stores_the_details_and_starts_as_a_current_student(): void
    {
        $this->post('/register', $this->signUpData(['member_status' => 'old', 'is_admin' => true]))
            ->assertRedirect('/verify-email');

        $student = User::firstWhere('email', 'student@example.com');

        $this->assertSame('S-2024-01', $student->student_id);
        $this->assertSame('B.Sc 3rd year', $student->education_level);
        $this->assertSame('01712345678', $student->mobile);
        $this->assertSame('current', $student->fresh()->member_status);
        $this->assertFalse($student->fresh()->is_admin);
    }

    public function test_sign_up_needs_student_id_education_level_and_mobile(): void
    {
        $this->post('/register', $this->signUpData(['student_id' => '', 'education_level' => '', 'mobile' => '']))
            ->assertSessionHasErrors(['student_id', 'education_level', 'mobile']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_student_id_must_be_unique(): void
    {
        User::factory()->create(['student_id' => 'S-2024-01']);

        $this->post('/register', $this->signUpData())->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('users', 1);
    }

    #[TestWith(['123'])]
    #[TestWith(['not a number'])]
    #[TestWith(['01712-abc-678'])]
    #[TestWith(['++8801712345678'])]
    public function test_mobile_must_look_like_a_phone_number(string $mobile): void
    {
        $this->post('/register', $this->signUpData(['mobile' => $mobile]))->assertSessionHasErrors('mobile');

        $this->assertDatabaseCount('users', 0);
    }

    #[TestWith(['01712345678'])]
    #[TestWith(['+8801712345678'])]
    #[TestWith(['017 1234 5678'])]
    #[TestWith(['017-1234-5678'])]
    public function test_common_phone_formats_are_accepted(string $mobile): void
    {
        $this->post('/register', $this->signUpData(['mobile' => $mobile]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_can_edit_student_details_and_member_status(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin());

        $this->get("/admin/users/{$student->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->component('admin/users/Form')
            ->where('user.student_id', $student->student_id)
            ->where('user.member_status', 'current')
            ->where('memberStatuses', User::MEMBER_STATUSES));

        $this->put("/admin/users/{$student->id}", [
            'name' => 'Renamed',
            'email' => $student->email,
            'student_id' => 'S-9',
            'education_level' => 'PhD',
            'mobile' => '01812345678',
            'member_status' => 'old',
        ])->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Renamed', $student->name);
        $this->assertSame('S-9', $student->student_id);
        $this->assertSame('PhD', $student->education_level);
        $this->assertSame('01812345678', $student->mobile);
        $this->assertSame('old', $student->member_status);
    }

    public function test_editing_without_the_new_fields_leaves_them_alone(): void
    {
        $student = User::factory()->create(['student_id' => 'S-5', 'member_status' => 'old']);

        $this->actingAs($this->admin())
            ->put("/admin/users/{$student->id}", ['name' => 'Only Name', 'email' => $student->email])
            ->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Only Name', $student->name);
        $this->assertSame('S-5', $student->student_id);
        $this->assertSame('old', $student->member_status);
    }

    public function test_admin_can_clear_the_optional_details_of_an_old_account(): void
    {
        $student = User::factory()->create(['student_id' => 'S-5', 'mobile' => '01712345678']);

        $this->actingAs($this->admin())
            ->put("/admin/users/{$student->id}", ['name' => $student->name, 'email' => $student->email, 'student_id' => '', 'mobile' => ''])
            ->assertSessionHasNoErrors();

        $this->assertNull($student->fresh()->student_id);
        $this->assertNull($student->fresh()->mobile);
    }

    public function test_student_id_stays_unique_when_editing(): void
    {
        User::factory()->create(['student_id' => 'S-1']);
        $student = User::factory()->create(['student_id' => 'S-2']);

        $this->actingAs($this->admin());

        $this->put("/admin/users/{$student->id}", ['name' => $student->name, 'email' => $student->email, 'student_id' => 'S-1'])
            ->assertSessionHasErrors('student_id');
        $this->assertSame('S-2', $student->fresh()->student_id);

        $this->put("/admin/users/{$student->id}", ['name' => $student->name, 'email' => $student->email, 'student_id' => 'S-2'])
            ->assertSessionHasNoErrors();
    }

    public function test_member_status_must_be_current_or_old(): void
    {
        $student = User::factory()->create();

        $this->actingAs($this->admin())
            ->put("/admin/users/{$student->id}", ['name' => $student->name, 'email' => $student->email, 'member_status' => 'alumni'])
            ->assertSessionHasErrors('member_status');

        $this->assertSame('current', $student->fresh()->member_status);
    }

    public function test_students_cannot_change_their_own_member_status_or_id(): void
    {
        $student = User::factory()->create(['student_id' => 'S-7']);

        $this->actingAs($student)
            ->put('/student-corner/profile', [
                'name' => $student->name,
                'email' => $student->email,
                'member_status' => 'old',
                'student_id' => 'S-8',
            ]);

        $student->refresh();
        $this->assertSame('current', $student->member_status);
        $this->assertSame('S-7', $student->student_id);
    }

    public function test_admin_list_shows_the_new_fields_and_searches_by_student_id(): void
    {
        User::factory()->create(['student_id' => 'S-FIND-ME', 'member_status' => 'old']);
        User::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->get('/admin/users?search=S-FIND')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.student_id', 'S-FIND-ME')
                ->where('users.data.0.member_status', 'old')
                ->missing('users.data.0.mobile'));
    }
}
