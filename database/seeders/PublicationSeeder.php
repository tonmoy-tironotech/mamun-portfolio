<?php

namespace Database\Seeders;

use App\Models\Publication;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

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
            $files = Arr::pull($publication, 'files', []);
            $created = Publication::create($publication);

            foreach ($files as $file) {
                $source = public_path($file);
                $path = 'publications/'.uniqid().'-'.$file;

                Storage::disk('local')->put($path, file_get_contents($source));

                $created->files()->create([
                    'file_path' => $path,
                    'file_name' => $file,
                    'file_size' => filesize($source),
                ]);
            }
        }
    }
}
