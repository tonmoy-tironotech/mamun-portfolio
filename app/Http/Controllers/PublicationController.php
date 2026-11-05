<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Models\PublicationFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));
        $type = $request->string('type')->toString();

        if (! array_key_exists($type, Publication::TYPES)) {
            $type = array_key_first(Publication::TYPES);
        }

        $counts = Publication::search($search)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Publications', [
            'publications' => Publication::search($search)
                ->with('files')
                ->where('type', $type)
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Publication $publication) => $publication->toPublicArray()),
            'types' => collect(Publication::TYPES)
                ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label, 'count' => (int) ($counts[$key] ?? 0)])
                ->values(),
            'activeType' => $type,
            'search' => $search,
        ]);
    }

    /**
     * Opens the PDF in the browser.
     */
    public function file(PublicationFile $file): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($file->file_path), 404);

        return Storage::disk('local')->response($file->file_path, $file->file_name, ['Content-Type' => 'application/pdf']);
    }
}
