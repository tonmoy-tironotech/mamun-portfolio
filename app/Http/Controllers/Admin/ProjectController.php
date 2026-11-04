<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Support\Html;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->string('search'));

        return Inertia::render('admin/projects/Index', [
            'projects' => Project::query()
                ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
                ->latest('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Project $project) => [
                    ...$project->only('id', 'title', 'slug', 'image', 'url'),
                    'updated' => $project->updated_at->format('d M Y'),
                ]),
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/projects/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, imageRequired: true);

        Project::create([
            ...$data,
            'slug' => Project::uniqueSlug($data['title']),
            'image' => $this->storeImage($request),
        ]);

        return to_route('admin.projects.index')->with('status', 'Project created.');
    }

    public function edit(Project $project): Response
    {
        return Inertia::render('admin/projects/Form', [
            'project' => [...$project->only('id', 'title', 'slug', 'image', 'url'), 'body' => $project->safe_body],
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request, imageRequired: false);

        if ($request->hasFile('image')) {
            $this->deleteImage($project);
            $data['image'] = $this->storeImage($request);
        } else {
            unset($data['image']);
        }

        $project->update($data);

        return to_route('admin.projects.index')->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->deleteImage($project);
        $project->delete();

        return back()->with('status', 'Project deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $imageRequired): array
    {
        $request->merge(['body' => Html::emptyIfBlank($request->input('body'))]);

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'body' => ['required', 'string', 'max:60000'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }

    private function storeImage(Request $request): string
    {
        return '/uploads/'.$request->file('image')->store('projects', 'uploads');
    }

    /**
     * Only uploaded images are removed; the seeded ones live in public/images.
     */
    private function deleteImage(Project $project): void
    {
        if (str_starts_with($project->image, '/uploads/')) {
            Storage::disk('uploads')->delete(substr($project->image, strlen('/uploads/')));
        }
    }
}
