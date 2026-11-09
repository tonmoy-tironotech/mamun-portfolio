<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('uploads');
    }

    private function photo(string $name = 'me.png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 300, 300);
    }

    private function storedPath(User $user): string
    {
        return substr($user->fresh()->profile_photo, strlen('/uploads/'));
    }

    public function test_guests_cannot_upload_or_remove_a_photo(): void
    {
        $this->post('/student-corner/profile/photo', ['photo' => $this->photo()])->assertRedirect('/login');
        $this->delete('/student-corner/profile/photo')->assertRedirect('/login');
    }

    public function test_a_student_must_verify_their_email_before_uploading(): void
    {
        $student = User::factory()->unverified()->create();

        $this->actingAs($student)
            ->post('/student-corner/profile/photo', ['photo' => $this->photo()])
            ->assertRedirect('/verify-email');

        $this->assertNull($student->fresh()->profile_photo);
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_a_verified_student_can_upload_a_photo_even_while_pending(): void
    {
        $student = User::factory()->pending()->create();

        $this->actingAs($student)
            ->from('/student-corner/profile')
            ->post('/student-corner/profile/photo', ['photo' => $this->photo()])
            ->assertRedirect('/student-corner/profile')
            ->assertSessionHas('status');

        $this->assertStringStartsWith('/uploads/profiles/', $student->fresh()->profile_photo);
        Storage::disk('uploads')->assertExists($this->storedPath($student));
    }

    public function test_uploading_again_replaces_the_old_file(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post('/student-corner/profile/photo', ['photo' => $this->photo('one.png')]);
        $first = $this->storedPath($student);

        $this->post('/student-corner/profile/photo', ['photo' => $this->photo('two.png')]);
        $second = $this->storedPath($student);

        $this->assertNotSame($first, $second);
        Storage::disk('uploads')->assertMissing($first);
        Storage::disk('uploads')->assertExists($second);
    }

    public function test_a_student_can_remove_their_photo(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post('/student-corner/profile/photo', ['photo' => $this->photo()]);
        $path = $this->storedPath($student);

        $this->delete('/student-corner/profile/photo')->assertSessionHas('status');

        $this->assertNull($student->fresh()->profile_photo);
        Storage::disk('uploads')->assertMissing($path);
    }

    #[TestWith(['shell.php'])]
    #[TestWith(['page.html'])]
    #[TestWith(['logo.svg'])]
    #[TestWith(['run.exe'])]
    public function test_only_image_files_are_accepted(string $name): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post('/student-corner/profile/photo', ['photo' => UploadedFile::fake()->create($name, 10)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($student->fresh()->profile_photo);
    }

    public function test_photos_over_two_megabytes_are_rejected(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post('/student-corner/profile/photo', ['photo' => UploadedFile::fake()->image('big.png')->size(2049)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($student->fresh()->profile_photo);
    }

    public function test_a_photo_is_required(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/student-corner/profile/photo', [])
            ->assertSessionHasErrors('photo');
    }

    public function test_the_photo_and_verification_state_are_shared_with_pages(): void
    {
        $student = User::factory()->create(['profile_photo' => '/uploads/profiles/me.jpg']);

        $this->actingAs($student)->get('/student-corner/profile')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.profile_photo', '/uploads/profiles/me.jpg')
            ->where('auth.user.email_verified', true));

        $this->actingAs(User::factory()->unverified()->create())->get('/student-corner/profile')->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.profile_photo', null)
            ->where('auth.user.email_verified', false));
    }

    public function test_admin_can_remove_a_students_photo(): void
    {
        Storage::disk('uploads')->put('profiles/student.jpg', 'image data');
        $student = User::factory()->create(['profile_photo' => '/uploads/profiles/student.jpg']);

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get("/admin/users/{$student->id}/edit")->assertInertia(fn (Assert $page) => $page
            ->where('user.profile_photo', '/uploads/profiles/student.jpg'));

        $this->delete("/admin/users/{$student->id}/photo")->assertSessionHas('status');

        $this->assertNull($student->fresh()->profile_photo);
        Storage::disk('uploads')->assertMissing('profiles/student.jpg');
    }

    public function test_only_admins_can_remove_someone_elses_photo(): void
    {
        $student = User::factory()->create(['profile_photo' => '/uploads/profiles/student.jpg']);

        $this->delete("/admin/users/{$student->id}/photo")->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->delete("/admin/users/{$student->id}/photo")
            ->assertRedirect('/student-corner');

        $this->assertSame('/uploads/profiles/student.jpg', $student->fresh()->profile_photo);
    }
}
