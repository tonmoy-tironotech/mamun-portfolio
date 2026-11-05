<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
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
        Publication::create([
            ...$this->validated($request),
            ...$this->storePdf($request),
        ]);

        return to_route('admin.publications.index')->with('status', 'Publication created.');
    }

    public function edit(Publication $publication): Response
    {
        return Inertia::render('admin/publications/Form', [
            'types' => Publication::TYPES,
            'publication' => [
                ...$publication->only('id', 'type', 'title', 'pdf_name', 'pdf_size', 'link_url'),
                'description' => Html::clean($publication->description),
                'has_pdf' => $publication->pdf_path !== null,
            ],
        ]);
    }

    public function update(Request $request, Publication $publication): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('pdf')) {
            $this->deletePdf($publication);
            $data = [...$data, ...$this->storePdf($request)];
        } elseif ($request->boolean('remove_pdf')) {
            $this->deletePdf($publication);
            $data = [...$data, 'pdf_path' => null, 'pdf_name' => null, 'pdf_size' => null];
        }

        $publication->update($data);

        return to_route('admin.publications.index')->with('status', 'Publication updated.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
        $this->deletePdf($publication);
        $publication->delete();

        return back()->with('status', 'Publication deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $request->merge(['description' => Html::emptyIfBlank($request->input('description'))]);

        return Arr::except($request->validate([
            'type' => ['required', Rule::in(array_keys(Publication::TYPES))],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['required', 'string', 'max:60000'],
            'link_url' => ['nullable', 'url:http,https', 'max:2000'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'remove_pdf' => ['nullable', 'boolean'],
        ]), ['pdf', 'remove_pdf']);
    }

    /**
     * The PDF goes on the private disk and is served through the public
     * publication PDF route, so storage:link isn't needed.
     *
     * @return array<string, mixed>
     */
    private function storePdf(Request $request): array
    {
        $file = $request->file('pdf');

        if ($file === null) {
            return [];
        }

        return [
            'pdf_path' => $file->store('publications', 'local'),
            'pdf_name' => basename($file->getClientOriginalName()),
            'pdf_size' => $file->getSize(),
        ];
    }

    private function deletePdf(Publication $publication): void
    {
        if ($publication->pdf_path !== null) {
            Storage::disk('local')->delete($publication->pdf_path);
        }
    }
}
