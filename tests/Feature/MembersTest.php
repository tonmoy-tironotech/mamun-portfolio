<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MembersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_anyone_can_open_the_members_page(): void
    {
        $this->get('/members')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Members')
                ->has('current.data', 0)
                ->has('old.data', 0));
    }

    public function test_only_approved_verified_students_are_listed(): void
    {
        User::factory()->create(['name' => 'Listed Student']);
        User::factory()->create(['name' => 'The Admin', 'is_admin' => true]);
        User::factory()->pending()->create(['name' => 'Waiting Student']);
        User::factory()->unverified()->create(['name' => 'Unverified Student']);
        User::factory()->rejected()->create(['name' => 'Rejected Student']);

        $this->get('/members')->assertInertia(fn (Assert $page) => $page
            ->has('current.data', 1)
            ->where('current.data.0.name', 'Listed Student')
            ->has('old.data', 0));
    }

    public function test_current_and_old_students_are_separate_sections_with_totals(): void
    {
        User::factory()->count(2)->create();
        User::factory()->old()->create(['name' => 'Alumni Student']);

        $this->get('/members')->assertInertia(fn (Assert $page) => $page
            ->has('current.data', 2)
            ->where('current.total', 2)
            ->has('old.data', 1)
            ->where('old.total', 1)
            ->where('old.data.0.name', 'Alumni Student'));
    }

    public function test_students_are_listed_alphabetically_with_only_the_public_details(): void
    {
        User::factory()->create(['name' => 'Zara', 'student_id' => 'S-3', 'education_level' => 'PhD', 'mobile' => '01999999999']);
        User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'student_id' => 'S-1',
            'education_level' => 'B.Sc',
            'profile_photo' => '/uploads/profiles/alice.jpg',
        ]);

        $response = $this->get('/members');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('current.data.0.name', 'Alice')
            ->where('current.data.0.email', 'alice@example.com')
            ->where('current.data.0.student_id', 'S-1')
            ->where('current.data.0.education_level', 'B.Sc')
            ->where('current.data.0.profile_photo', '/uploads/profiles/alice.jpg')
            ->where('current.data.1.name', 'Zara')
            ->where('current.data.1.profile_photo', null)
            ->missing('current.data.1.mobile')
            ->missing('current.data.1.password')
            ->missing('current.data.1.approved_at'));

        $response->assertDontSee('01999999999');
    }

    public function test_students_without_details_show_empty_values(): void
    {
        User::factory()->create(['student_id' => null, 'education_level' => null]);

        $this->get('/members')->assertInertia(fn (Assert $page) => $page
            ->where('current.data.0.student_id', null)
            ->where('current.data.0.education_level', null));
    }

    public function test_each_section_has_its_own_pagination(): void
    {
        User::factory()->count(9)->create();
        User::factory()->count(9)->old()->create();

        $this->get('/members')->assertInertia(fn (Assert $page) => $page
            ->has('current.data', 8)
            ->where('current.last_page', 2)
            ->has('old.data', 8)
            ->where('old.last_page', 2));

        $this->get('/members?current_page=2')->assertInertia(fn (Assert $page) => $page
            ->has('current.data', 1)
            ->where('current.current_page', 2)
            ->has('old.data', 8)
            ->where('old.current_page', 1)
            ->where('old.next_page_url', fn ($url) => str_contains($url, 'current_page=2')));

        $this->get('/members?old_page=2')->assertInertia(fn (Assert $page) => $page
            ->has('old.data', 1)
            ->where('old.current_page', 2)
            ->where('current.current_page', 1));
    }

    public function test_search_engines_are_told_not_to_list_the_page(): void
    {
        $this->get('/members')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
