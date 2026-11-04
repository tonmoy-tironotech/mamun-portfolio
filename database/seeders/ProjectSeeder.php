<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Load the initial projects. Skipped once projects exist, so edits are kept.
     */
    public function run(): void
    {
        if (Project::exists()) {
            return;
        }

        // Newest projects are listed first, so insert in reverse to keep the original order
        foreach (array_reverse(require database_path('data/projects.php')) as $project) {
            Project::create($project);
        }
    }
}
