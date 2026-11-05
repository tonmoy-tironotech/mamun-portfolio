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
            $pdf = Arr::pull($publication, 'pdf');
            $link = Arr::pull($publication, 'link');

            if ($pdf !== null) {
                $source = public_path($pdf);
                $path = 'publications/'.uniqid().'-'.$pdf;

                Storage::disk('local')->put($path, file_get_contents($source));

                $publication = [...$publication, 'pdf_path' => $path, 'pdf_name' => $pdf, 'pdf_size' => filesize($source)];
            }

            Publication::create([...$publication, 'link_url' => $link]);
        }
    }
}
