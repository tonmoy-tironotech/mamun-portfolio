<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Support\Html;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));

        return Inertia::render('admin/documents/Index', [
            'documents' => Document::search($search)
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Document $document) => [
                    ...$document->toListArray(),
                    'updated' => $document->updated_at->format('d M Y'),
                ]),
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/documents/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        Document::create([
            ...$this->validated($request, fileRequired: true),
            ...$this->storeFile($request),
        ]);

        return to_route('admin.documents.index')->with('status', 'Document created.');
    }

    public function edit(Document $document): Response
    {
        return Inertia::render('admin/documents/Form', [
            'document' => $document->toDetailArray(),
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        $data = $this->validated($request, fileRequired: false);

        if ($request->hasFile('file')) {
            Storage::disk('local')->delete($document->file_path);
            $data = [...$data, ...$this->storeFile($request)];
        }

        $document->update($data);

        return to_route('admin.documents.index')->with('status', 'Document updated.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('status', 'Document deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $fileRequired): array
    {
        $request->merge([
            'short_description' => Html::emptyIfBlank($request->input('short_description')),
            'long_description' => Html::emptyIfBlank($request->input('long_description')),
        ]);

        return Arr::except($request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string', 'max:5000'],
            'long_description' => ['required', 'string', 'max:60000'],
            'file' => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,csv,zip,jpg,jpeg,png',
                'max:20480',
            ],
        ]), 'file');
    }

    /**
     * Files go on the private disk so they can only be reached through the
     * logged-in download route, never by a direct URL.
     *
     * @return array<string, mixed>
     */
    private function storeFile(Request $request): array
    {
        $file = $request->file('file');

        return [
            'file_path' => $file->store('documents', 'local'),
            'file_name' => basename($file->getClientOriginalName()),
            'file_size' => $file->getSize(),
        ];
    }
}
