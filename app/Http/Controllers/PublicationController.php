<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
