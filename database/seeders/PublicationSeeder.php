<?php

namespace Database\Seeders;

use App\Models\Publication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PublicationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Load the initial publications. Skipped once publications exist, so edits are kept.
     */
    public function run(): void
    {
        if (Publication::exists()) {
            return;
        }

        // Newest publications are listed first, so insert in reverse to keep the order
        foreach (array_reverse(require database_path('data/publications.php')) as $publication) {
            Publication::create($publication);
        }
    }
}
