<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Publication;
use App\Support\Html;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        Publication::create($this->validated($request));

        return to_route('admin.publications.index')->with('status', 'Publication created.');
    }

    public function edit(Publication $publication): Response
    {
        return Inertia::render('admin/publications/Form', [
            'types' => Publication::TYPES,
            'publication' => [
                ...$publication->only('id', 'type', 'title'),
                'description' => Html::clean($publication->description),
            ],
        ]);
    }

    public function update(Request $request, Publication $publication): RedirectResponse
    {
        $publication->update($this->validated($request));

        return to_route('admin.publications.index')->with('status', 'Publication updated.');
    }

    public function destroy(Publication $publication): RedirectResponse
    {
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
        ]);
    }
}
