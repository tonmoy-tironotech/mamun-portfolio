<?php

namespace Tests\Feature;

use App\Models\Publication;
use App\Models\User;
use Database\Seeders\PublicationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('local');
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
        Storage::fake('local');

        $this->seed(PublicationSeeder::class);
        $this->seed(PublicationSeeder::class);

        $this->assertDatabaseCount('publications', 10);
        $this->assertSame(3, Publication::whereNotNull('pdf_path')->count());
        $this->assertSame(1, Publication::whereNotNull('link_url')->count());
    }

    public function test_unknown_type_falls_back_to_the_first_tab(): void
    {
        $this->get('/publications?type=nope')->assertOk()->assertInertia(fn (Assert $page) => $page->where('activeType', 'journal'));
    }

    public function test_a_new_publication_is_listed_first(): void
    {
        Storage::fake('local');
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

    public function test_admin_can_attach_a_pdf_and_a_link(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());

        $this->post('/admin/publications', [
            'type' => 'journal',
            'title' => 'With extras',
            'description' => '<p>Text</p>',
            'pdf' => UploadedFile::fake()->create('paper.pdf', 100, 'application/pdf'),
            'link_url' => 'https://example.com/paper',
        ])->assertRedirect('/admin/publications');

        $publication = Publication::firstWhere('title', 'With extras');
        Storage::disk('local')->assertExists($publication->pdf_path);
        $this->assertSame('paper.pdf', $publication->pdf_name);

        $this->get('/publications')->assertInertia(fn (Assert $page) => $page
            ->where('publications.data.0.has_pdf', true)
            ->where('publications.data.0.link_url', 'https://example.com/paper')
            ->missing('publications.data.0.pdf_path'));

        $this->get("/publications/{$publication->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_a_publication_without_a_pdf_has_no_pdf_download(): void
    {
        $publication = $this->makePublication();

        $this->get("/publications/{$publication->id}/pdf")->assertNotFound();
        $this->get('/publications')->assertInertia(fn (Assert $page) => $page
            ->where('publications.data.0.has_pdf', false)
            ->where('publications.data.0.link_url', null));
    }

    public function test_pdf_can_be_replaced_and_removed_when_editing(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());
        Storage::disk('local')->put('publications/old.pdf', 'x');
        $publication = $this->makePublication(['pdf_path' => 'publications/old.pdf', 'pdf_name' => 'old.pdf', 'pdf_size' => 1]);
        $fields = ['type' => 'journal', 'title' => 'A paper', 'description' => '<p>Some authors</p>'];

        $this->put("/admin/publications/{$publication->id}", [...$fields, 'pdf' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf')])
            ->assertRedirect('/admin/publications');

        $publication->refresh();
        $this->assertSame('new.pdf', $publication->pdf_name);
        Storage::disk('local')->assertMissing('publications/old.pdf');
        Storage::disk('local')->assertExists($publication->pdf_path);

        $this->put("/admin/publications/{$publication->id}", [...$fields, 'remove_pdf' => true])->assertRedirect('/admin/publications');

        $this->assertNull($publication->fresh()->pdf_path);
        $this->assertSame([], Storage::disk('local')->files('publications'));
    }

    public function test_deleting_a_publication_removes_its_pdf(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());
        Storage::disk('local')->put('publications/x.pdf', 'x');
        $publication = $this->makePublication(['pdf_path' => 'publications/x.pdf', 'pdf_name' => 'x.pdf', 'pdf_size' => 1]);

        $this->delete("/admin/publications/{$publication->id}");

        Storage::disk('local')->assertMissing('publications/x.pdf');
        $this->assertDatabaseCount('publications', 0);
    }

    public function test_only_a_pdf_and_a_web_link_are_accepted(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());

        $this->post('/admin/publications', [
            'type' => 'journal',
            'title' => 'Bad input',
            'description' => '<p>Text</p>',
            'pdf' => UploadedFile::fake()->create('a.exe', 10, 'application/octet-stream'),
            'link_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['pdf', 'link_url']);

        $this->assertDatabaseCount('publications', 0);
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
