<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\PublicationFile;
use App\Support\Html;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PublicationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));

        return Inertia::render('admin/publications/Index', [
            'publications' => Publication::search($search)
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Publication $publication) => [
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'type' => Publication::TYPES[$publication->type],
                    'description' => Html::text($publication->description),
                    'updated' => $publication->updated_at->format('d M Y'),
                ]),
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/publications/Form', ['types' => Publication::TYPES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $publication = Publication::create(Arr::except($this->validated($request), ['files', 'remove_files']));
        $this->storeFiles($request, $publication);

        return to_route('admin.publications.index')->with('status', 'Publication created.');
    }

    public function edit(Publication $publication): Response
    {
        return Inertia::render('admin/publications/Form', [
            'types' => Publication::TYPES,
            'publication' => [
                ...$publication->only('id', 'type', 'title'),
                'description' => Html::clean($publication->description),
                'files' => $publication->filesArray(),
            ],
        ]);
    }

    public function update(Request $request, Publication $publication): RedirectResponse
    {
        $publication->update(Arr::except($this->validated($request), ['files', 'remove_files']));

        $publication->files()->whereKey($request->input('remove_files', []))->get()->each(function (PublicationFile $file) {
            Storage::disk('local')->delete($file->file_path);
            $file->delete();
        });
        $this->storeFiles($request, $publication);

        return to_route('admin.publications.index')->with('status', 'Publication updated.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $publication->files->each(fn (PublicationFile $file) => Storage::disk('local')->delete($file->file_path));
        $publication->delete();

        return back()->with('status', 'Publication deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $request->merge(['description' => Html::emptyIfBlank($request->input('description'))]);

        return $request->validate([
            'type' => ['required', Rule::in(array_keys(Publication::TYPES))],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:60000'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['file', 'mimes:pdf', 'max:20480'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
        ]);
    }

    /**
     * Uploaded PDFs go on the private disk and are served through the public
     * publications file route, so storage:link isn't needed.
     */
    private function storeFiles(Request $request, Publication $publication): void
    {
        foreach ($request->file('files', []) as $file) {
            $publication->files()->create([
                'file_path' => $file->store('publications', 'local'),
                'file_name' => basename($file->getClientOriginalName()),
                'file_size' => $file->getSize(),
            ]);
        }
    }
}
