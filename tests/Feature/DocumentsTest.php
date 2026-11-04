<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_guests_cannot_see_or_download_documents(): void
    {
        $document = $this->makeDocument();

        $this->get('/student-corner')->assertRedirect('/login');
        $this->get("/student-corner/documents/{$document->id}")->assertRedirect('/login');
        $this->get("/student-corner/documents/{$document->id}/download")->assertRedirect('/login');
    }

    public function test_uploaded_files_are_not_in_the_public_folder(): void
    {
        $this->actingAs($this->admin())->post('/admin/documents', $this->validData())->assertRedirect('/admin/documents');

        $document = Document::first();

        Storage::disk('local')->assertExists($document->file_path);
        $this->assertFileDoesNotExist(public_path($document->file_path));
        $this->assertSame('notes.pdf', $document->file_name);
    }

    public function test_student_sees_documents_with_search_and_pagination(): void
    {
        foreach (range(1, 12) as $number) {
            $this->makeDocument(['title' => "Lecture {$number}"]);
        }
        $this->makeDocument(['title' => 'Exam guide', 'short_description' => 'How to prepare']);

        $this->actingAs(User::factory()->create());

        $this->get('/student-corner')->assertInertia(fn (Assert $page) => $page
            ->component('Student')
            ->has('documents.data', 10)
            ->where('documents.total', 13)
            ->where('documents.last_page', 2)
            ->where('documents.data.0.title', 'Exam guide')
            ->missing('documents.data.0.file_path'));

        $this->get('/student-corner?page=2')->assertInertia(fn (Assert $page) => $page->has('documents.data', 3));

        $this->get('/student-corner?search=Exam')->assertInertia(fn (Assert $page) => $page->has('documents.data', 1)->where('search', 'Exam'));
        $this->get('/student-corner?search=prepare')->assertInertia(fn (Assert $page) => $page->has('documents.data', 1));
        $this->get('/student-corner?search=nothing-here')->assertInertia(fn (Assert $page) => $page->has('documents.data', 0));
    }

    public function test_student_can_view_more_and_download(): void
    {
        $document = $this->makeDocument();
        Storage::disk('local')->put($document->file_path, 'file contents');

        $this->actingAs(User::factory()->create());

        $this->get("/student-corner/documents/{$document->id}")->assertInertia(fn (Assert $page) => $page
            ->component('DocumentDetail')
            ->where('document.long_description', 'A long description')
            ->missing('document.file_path'));

        $this->get("/student-corner/documents/{$document->id}/download")
            ->assertOk()
            ->assertDownload('notes.pdf');
    }

    public function test_descriptions_are_rich_text_on_pages_and_plain_in_tables(): void
    {
        $document = $this->makeDocument([
            'short_description' => '<p>One &amp; <strong>two</strong></p><p>three<script>alert(1)</script></p>',
            'long_description' => '<h2>Head</h2><ul><li>item</li></ul><img src=x onerror=alert(2)>',
        ]);

        $this->actingAs(User::factory()->create());

        $this->get('/student-corner')->assertInertia(fn (Assert $page) => $page
            ->where('documents.data.0.short_description', 'One & two three'));

        $this->get("/student-corner/documents/{$document->id}")->assertInertia(fn (Assert $page) => $page
            ->where('document.short_description', '<p>One &amp; <strong>two</strong></p><p>three</p>')
            ->where('document.long_description', '<h2>Head</h2><ul><li>item</li></ul>'));
    }

    public function test_blank_editors_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/documents', $this->validData(['short_description' => '<p></p>', 'long_description' => '<p><br></p>']))
            ->assertSessionHasErrors(['short_description', 'long_description']);

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_download_of_a_missing_file_returns_404(): void
    {
        $document = $this->makeDocument();

        $this->actingAs(User::factory()->create())
            ->get("/student-corner/documents/{$document->id}/download")
            ->assertNotFound();
    }

    public function test_students_cannot_manage_documents(): void
    {
        $document = $this->makeDocument();
        $student = User::factory()->create();

        $this->actingAs($student)->get('/admin/documents')->assertRedirect('/student-corner');
        $this->actingAs($student)->post('/admin/documents', $this->validData())->assertRedirect('/student-corner');
        $this->actingAs($student)->put("/admin/documents/{$document->id}", ['title' => 'x'])->assertRedirect('/student-corner');
        $this->actingAs($student)->delete("/admin/documents/{$document->id}")->assertRedirect('/student-corner');

        $this->assertDatabaseCount('documents', 1);
        $this->assertDatabaseHas('documents', ['id' => $document->id, 'title' => 'Notes']);
    }

    public function test_admin_list_can_be_searched(): void
    {
        $this->makeDocument(['title' => 'Alpha']);
        $this->makeDocument(['title' => 'Beta']);

        $this->actingAs($this->admin())
            ->get('/admin/documents?search=Beta')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documents/Index')
                ->has('documents.data', 1)
                ->where('documents.data.0.title', 'Beta'));
    }

    public function test_required_fields_and_file_types_are_validated(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/documents', [])->assertSessionHasErrors(['title', 'short_description', 'long_description', 'file']);

        foreach (['shell.php', 'page.html', 'logo.svg', 'run.exe'] as $name) {
            $this->post('/admin/documents', $this->validData(['file' => UploadedFile::fake()->create($name, 10)]))
                ->assertSessionHasErrors('file');
        }

        $this->post('/admin/documents', $this->validData(['file' => UploadedFile::fake()->create('big.pdf', 20481, 'application/pdf')]))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_updating_keeps_the_file_unless_a_new_one_is_sent(): void
    {
        $document = $this->makeDocument();
        Storage::disk('local')->put($document->file_path, 'old');

        $this->actingAs($this->admin());

        $this->put("/admin/documents/{$document->id}", [
            'title' => 'Changed',
            'short_description' => 'Short',
            'long_description' => 'Long',
        ])->assertRedirect('/admin/documents');

        $this->assertSame('Changed', $document->fresh()->title);
        Storage::disk('local')->assertExists($document->file_path);

        $old = $document->file_path;
        $this->put("/admin/documents/{$document->id}", $this->validData(['file' => UploadedFile::fake()->create('new.pdf', 10, 'application/pdf')]));

        Storage::disk('local')->assertMissing($old);
        $this->assertSame('new.pdf', $document->fresh()->file_name);
        Storage::disk('local')->assertExists($document->fresh()->file_path);
    }

    public function test_deleting_removes_the_file(): void
    {
        $document = $this->makeDocument();
        Storage::disk('local')->put($document->file_path, 'file contents');

        $this->actingAs($this->admin())->delete("/admin/documents/{$document->id}");

        Storage::disk('local')->assertMissing($document->file_path);
        $this->assertDatabaseCount('documents', 0);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return [
            'title' => 'Notes',
            'short_description' => 'Short',
            'long_description' => 'Long',
            'file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeDocument(array $attributes = []): Document
    {
        return Document::create([
            'title' => 'Notes',
            'short_description' => 'A short description',
            'long_description' => 'A long description',
            'file_path' => 'documents/notes.pdf',
            'file_name' => 'notes.pdf',
            'file_size' => 2048,
            ...$attributes,
        ]);
    }
}
