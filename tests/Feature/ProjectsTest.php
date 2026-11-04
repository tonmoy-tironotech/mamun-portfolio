<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('uploads');
    }

    public function test_projects_are_paginated_five_per_page_in_the_original_order(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->get('/projects')->assertInertia(fn (Assert $page) => $page
            ->component('Projects')
            ->has('projects', 5)
            ->where('projects.0.slug', 'cci-mobile-research-platform')
            ->where('currentPage', 1)
            ->where('lastPage', 2));

        $this->get('/projects?page=2')->assertInertia(fn (Assert $page) => $page
            ->has('projects', 2)
            ->where('projects.1.slug', 'cpu-cache-optimization')
            ->where('currentPage', 2));
    }

    public function test_out_of_range_pages_are_clamped(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->get('/projects?page=99')->assertInertia(fn (Assert $page) => $page->where('currentPage', 2));
        $this->get('/projects?page=0')->assertInertia(fn (Assert $page) => $page->where('currentPage', 1));
    }

    public function test_empty_list_renders(): void
    {
        $this->get('/projects')->assertOk()->assertInertia(fn (Assert $page) => $page->has('projects', 0)->where('lastPage', 1));
    }

    public function test_every_seeded_project_has_a_detail_page_and_an_image(): void
    {
        $this->seed(ProjectSeeder::class);

        foreach (Project::all() as $project) {
            $this->assertFileExists(public_path(ltrim($project->image, '/')));

            $this->get("/projects/{$project->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('ProjectDetail')
                ->where('project.title', $project->title));
        }
    }

    public function test_seeded_text_keeps_its_formatting(): void
    {
        $this->seed(ProjectSeeder::class);

        $body = Project::firstWhere('slug', 'cci-mobile-research-platform')->safe_body;

        $this->assertStringContainsString('<a href="https://www.utdallas.edu/~hussnain.ali/CCIMobile.html"', $body);
        $this->assertStringContainsString('<strong> Tools used: Verilog, Xilinx ISE, Modelsim</strong>', $body);
    }

    public function test_unknown_project_returns_404(): void
    {
        $this->get('/projects/does-not-exist')->assertNotFound();
    }

    public function test_guests_cannot_change_projects(): void
    {
        $project = $this->makeProject();

        $this->post('/admin/projects', [])->assertRedirect('/login');
        $this->put("/admin/projects/{$project->id}", [])->assertRedirect('/login');
        $this->delete("/admin/projects/{$project->id}")->assertRedirect('/login');

        $this->assertDatabaseCount('projects', 1);
    }

    public function test_project_can_be_created_with_an_image(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/projects', [
                'title' => 'My New Project',
                'url' => 'https://example.com',
                'body' => 'Some text',
                'image' => UploadedFile::fake()->image('photo.png'),
            ])
            ->assertRedirect('/admin/projects');

        $project = Project::firstWhere('slug', 'my-new-project');

        $this->assertStringStartsWith('/uploads/projects/', $project->image);
        Storage::disk('uploads')->assertExists(substr($project->image, strlen('/uploads/')));
        $this->get('/projects/my-new-project')->assertOk();
    }

    public function test_duplicate_titles_get_unique_slugs(): void
    {
        $this->makeProject(['title' => 'Same', 'slug' => 'same']);

        $this->assertSame('same-2', Project::uniqueSlug('Same'));
    }

    public function test_only_images_can_be_uploaded(): void
    {
        foreach (['shell.php', 'page.html', 'logo.svg'] as $name) {
            $this->actingAs($this->admin())
                ->post('/admin/projects', [
                    'title' => 'Bad',
                    'body' => 'Text',
                    'image' => UploadedFile::fake()->create($name, 10),
                ])
                ->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_project_link_must_be_http(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/projects', [
                'title' => 'Bad',
                'url' => 'javascript:alert(1)',
                'body' => 'Text',
                'image' => UploadedFile::fake()->image('photo.png'),
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_updating_keeps_the_image_unless_a_new_one_is_sent(): void
    {
        $project = $this->makeProject();

        $this->actingAs($this->admin())
            ->put("/admin/projects/{$project->id}", ['title' => 'Changed', 'body' => 'New text'])
            ->assertRedirect('/admin/projects');

        $project->refresh();
        $this->assertSame('Changed', $project->title);
        $this->assertSame('/images/Leg.jpg', $project->image);
    }

    public function test_replacing_and_deleting_removes_uploaded_files(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post('/admin/projects', [
            'title' => 'With image',
            'body' => 'Text',
            'image' => UploadedFile::fake()->image('one.png'),
        ]);
        $project = Project::firstWhere('title', 'With image');
        $first = substr($project->image, strlen('/uploads/'));

        $this->actingAs($user)->put("/admin/projects/{$project->id}", [
            'title' => 'With image',
            'body' => 'Text',
            'image' => UploadedFile::fake()->image('two.png'),
        ]);
        $second = substr($project->fresh()->image, strlen('/uploads/'));

        Storage::disk('uploads')->assertMissing($first);
        Storage::disk('uploads')->assertExists($second);

        $this->actingAs($user)->delete("/admin/projects/{$project->id}");

        Storage::disk('uploads')->assertMissing($second);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_editor_formatting_is_kept_and_blank_editor_is_rejected(): void
    {
        $project = $this->makeProject([
            'body' => '<h2>Head</h2><p><strong>b</strong> <em>i</em> <u>u</u></p><ul><li>one</li></ul><ol><li>two</li></ol><blockquote><p>q</p></blockquote>',
        ]);

        foreach (['<h2>Head</h2>', '<strong>b</strong>', '<em>i</em>', '<u>u</u>', '<ul><li>one</li></ul>', '<ol><li>two</li></ol>', '<blockquote>'] as $html) {
            $this->assertStringContainsString($html, $project->safe_body);
        }

        $this->actingAs($this->admin())
            ->put("/admin/projects/{$project->id}", ['title' => 'Same', 'body' => '<p></p>'])
            ->assertSessionHasErrors('body');

        $this->get("/admin/projects/{$project->id}/edit")->assertInertia(fn (Assert $page) => $page->where('project.body', $project->safe_body));
    }

    public function test_body_is_sanitized_before_it_reaches_the_page(): void
    {
        $project = $this->makeProject([
            'body' => 'Hi <script>alert(1)</script><img src=x onerror=alert(2)><strong onclick="alert(3)">bold</strong> <a href="javascript:alert(4)">bad</a> <a href="https://example.com">good</a>',
        ]);

        $this->get("/projects/{$project->slug}")->assertInertia(function (Assert $page) {
            $body = $page->toArray()['props']['project']['body'];

            $this->assertStringNotContainsString('<script', $body);
            $this->assertStringNotContainsString('onerror', $body);
            $this->assertStringNotContainsString('onclick', $body);
            $this->assertStringNotContainsString('javascript:', $body);
            $this->assertStringContainsString('<strong>bold</strong>', $body);
            $this->assertStringContainsString('href="https://example.com"', $body);
        });

        $this->get('/projects')->assertInertia(function (Assert $page) {
            $this->assertStringNotContainsString('<script', $page->toArray()['props']['projects'][0]['body']);
        });
    }

    public function test_students_cannot_change_projects(): void
    {
        $project = $this->makeProject();
        $student = User::factory()->create();

        $this->actingAs($student)->get('/admin/projects')->assertRedirect('/student-corner');
        $this->actingAs($student)->post('/admin/projects', ['title' => 'x', 'body' => 'y'])->assertRedirect('/student-corner');
        $this->actingAs($student)->put("/admin/projects/{$project->id}", ['title' => 'x', 'body' => 'y'])->assertRedirect('/student-corner');
        $this->actingAs($student)->delete("/admin/projects/{$project->id}")->assertRedirect('/student-corner');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'A project']);
    }

    public function test_admin_list_can_be_searched_and_paginated(): void
    {
        $this->seed(ProjectSeeder::class);

        foreach (range(1, 8) as $number) {
            $this->makeProject(['title' => "Extra {$number}", 'slug' => "extra-{$number}"]);
        }

        $this->actingAs($this->admin())->get('/admin/projects')->assertInertia(fn (Assert $page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 10)
            ->where('projects.total', 15)
            ->where('projects.last_page', 2));

        $this->get('/admin/projects?search=FPGA')->assertInertia(fn (Assert $page) => $page
            ->has('projects.data', 1)
            ->where('search', 'FPGA')
            ->where('projects.data.0.slug', 'fpga-heartbeat-detector'));

        $this->get('/admin/projects?search=Extra&page=1')->assertInertia(fn (Assert $page) => $page
            ->where('projects.total', 8)
            ->where('projects.first_page_url', fn ($url) => str_contains($url, 'search=Extra')));

        $this->get('/admin/projects?search=nothing-like-this')->assertInertia(fn (Assert $page) => $page->has('projects.data', 0));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProject(array $attributes = []): Project
    {
        return Project::create([
            'title' => 'A project',
            'slug' => 'a-project',
            'image' => '/images/Leg.jpg',
            'body' => 'Text',
            ...$attributes,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }
}
