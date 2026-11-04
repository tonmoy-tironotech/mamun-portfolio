<?php

namespace Tests\Feature;

use App\Models\Publication;
use App\Models\User;
use Database\Seeders\PublicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_seeded_publications_are_grouped_with_the_latest_first(): void
    {
        $this->seed(PublicationSeeder::class);

        $this->get('/publications')->assertInertia(fn (Assert $page) => $page
            ->component('Publications')
            ->where('activeType', 'journal')
            ->has('publications.data', 4)
            ->where('publications.data.0.title', 'A Spectrogram Orthogonal Polynomial Measure (SOPM) for the Prediction of Speech Intelligibility and Quality')
            ->where('types.0.count', 4)
            ->where('types.1.count', 4)
            ->where('types.2.count', 2));

        $this->get('/publications?type=abstract')->assertInertia(fn (Assert $page) => $page
            ->where('activeType', 'abstract')
            ->has('publications.data', 2));
    }

    public function test_seeder_only_runs_once(): void
    {
        $this->seed(PublicationSeeder::class);
        $this->seed(PublicationSeeder::class);

        $this->assertDatabaseCount('publications', 10);
    }

    public function test_unknown_type_falls_back_to_the_first_tab(): void
    {
        $this->get('/publications?type=nope')->assertOk()->assertInertia(fn (Assert $page) => $page->where('activeType', 'journal'));
    }

    public function test_a_new_publication_is_listed_first(): void
    {
        $this->seed(PublicationSeeder::class);
        $this->makePublication(['title' => 'Newest paper']);

        $this->get('/publications')->assertInertia(fn (Assert $page) => $page->where('publications.data.0.title', 'Newest paper'));
    }

    public function test_numbering_continues_across_pages(): void
    {
        foreach (range(1, 12) as $number) {
            $this->makePublication(['title' => "Paper {$number}"]);
        }

        $this->get('/publications')->assertInertia(fn (Assert $page) => $page
            ->has('publications.data', 10)
            ->where('publications.from', 1)
            ->where('publications.last_page', 2));

        $this->get('/publications?page=2')->assertInertia(fn (Assert $page) => $page
            ->has('publications.data', 2)
            ->where('publications.from', 11));
    }

    public function test_search_covers_the_active_tab_and_updates_the_counts(): void
    {
        $this->makePublication(['title' => 'Speech paper']);
        $this->makePublication(['title' => 'Other paper', 'description' => '<p>about speech</p>']);
        $this->makePublication(['title' => 'Unrelated']);
        $this->makePublication(['type' => 'conference', 'title' => 'Speech talk']);

        $this->get('/publications?search=speech')->assertInertia(fn (Assert $page) => $page
            ->has('publications.data', 2)
            ->where('search', 'speech')
            ->where('types.0.count', 2)
            ->where('types.1.count', 1)
            ->where('types.2.count', 0));

        $this->get('/publications?type=conference&search=speech')->assertInertia(fn (Assert $page) => $page
            ->has('publications.data', 1)
            ->where('publications.first_page_url', fn ($url) => str_contains($url, 'type=conference') && str_contains($url, 'search=speech')));
    }

    public function test_description_is_sanitized_on_the_public_page(): void
    {
        $this->makePublication(['description' => '<p><strong>ok</strong><script>alert(1)</script><img src=x onerror=alert(2)></p>']);

        $this->get('/publications')->assertInertia(fn (Assert $page) => $page
            ->where('publications.data.0.description', '<p><strong>ok</strong></p>'));
    }

    public function test_admin_can_manage_publications(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/publications', ['type' => 'conference', 'title' => 'Created', 'description' => '<p>Text</p>'])
            ->assertRedirect('/admin/publications');

        $publication = Publication::firstWhere('title', 'Created');
        $this->assertSame('conference', $publication->type);

        $this->get('/admin/publications')->assertInertia(fn (Assert $page) => $page
            ->component('admin/publications/Index')
            ->where('publications.data.0.type', 'Conference Papers')
            ->where('publications.data.0.description', 'Text'));

        $this->get('/admin/publications?search=nothing')->assertInertia(fn (Assert $page) => $page->has('publications.data', 0));

        $this->get("/admin/publications/{$publication->id}/edit")->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/publications/Form')
            ->where('publication.description', '<p>Text</p>'));

        $this->put("/admin/publications/{$publication->id}", ['type' => 'abstract', 'title' => 'Changed', 'description' => '<p>New</p>'])
            ->assertRedirect('/admin/publications');
        $this->assertSame('abstract', $publication->fresh()->type);

        $this->delete("/admin/publications/{$publication->id}");
        $this->assertDatabaseCount('publications', 0);
    }

    public function test_input_is_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/publications', ['type' => 'nope', 'title' => '', 'description' => '<p></p>'])
            ->assertSessionHasErrors(['type', 'title', 'description']);

        $this->assertDatabaseCount('publications', 0);
    }

    public function test_students_and_guests_cannot_manage_publications(): void
    {
        $publication = $this->makePublication();

        $this->get('/admin/publications')->assertRedirect('/login');
        $this->post('/admin/publications', [])->assertRedirect('/login');

        $student = User::factory()->create();
        $this->actingAs($student)->get('/admin/publications')->assertRedirect('/student-corner');
        $this->actingAs($student)->delete("/admin/publications/{$publication->id}")->assertRedirect('/student-corner');

        $this->assertDatabaseCount('publications', 1);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePublication(array $attributes = []): Publication
    {
        return Publication::create([
            'type' => 'journal',
            'title' => 'A paper',
            'description' => '<p>Some authors</p>',
            ...$attributes,
        ]);
    }
}
