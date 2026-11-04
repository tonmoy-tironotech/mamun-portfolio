<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    private const PER_PAGE = 5;

    public function index(Request $request): Response
    {
        $lastPage = max((int) ceil(Project::count() / self::PER_PAGE), 1);
        $currentPage = min(max($request->integer('page', 1), 1), $lastPage);

        return Inertia::render('Projects', [
            'projects' => Project::latest('id')
                ->forPage($currentPage, self::PER_PAGE)
                ->get()
                ->map->toPublicArray(),
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
        ]);
    }

    public function show(Project $project): Response
    {
        return Inertia::render('ProjectDetail', ['project' => $project->toPublicArray()]);
    }
}
