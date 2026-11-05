<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));

        return Inertia::render('Student', [
            'documents' => $request->user()->isApproved()
                ? Document::search($search)
                    ->latest('id')
                    ->paginate(10)
                    ->withQueryString()
                    ->through(fn (Document $document) => $document->toListArray())
                : null,
            'isRejected' => $request->user()->rejected_at !== null,
            'search' => $search,
        ]);
    }

    public function show(Document $document): Response
    {
        return Inertia::render('DocumentDetail', [
            'document' => $document->toDetailArray(),
        ]);
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }
}
