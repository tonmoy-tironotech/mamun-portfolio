<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DocumentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Load starter documents from the PDFs that ship with the site. Skipped
     * once documents exist, so edits are kept.
     */
    public function run(): void
    {
        if (Document::exists()) {
            return;
        }

        $documents = [
            [
                'title' => 'Curriculum Vitae (2018)',
                'short_description' => '<p>Curriculum vitae of Nursadul Mamun.</p>',
                'long_description' => '<p>The 2018 curriculum vitae of Nursadul Mamun, available as a PDF download.</p>',
                'file' => 'Nursadul_Mamun_CV.pdf',
            ],
            [
                'title' => 'Short Resume (2020)',
                'short_description' => '<p>Short resume of Nursadul Mamun.</p>',
                'long_description' => '<p>The short resume of Nursadul Mamun, prepared in 2020, available as a PDF download.</p>',
                'file' => '2020-Nursadul-Mamun-Resume-Internship-2020.pdf',
            ],
            [
                'title' => 'Full CV (July 2020)',
                'short_description' => '<p>Full CV of Nursadul Mamun.</p>',
                'long_description' => '<p>The full CV of Nursadul Mamun from July 2020, available as a PDF download.</p>',
                'file' => '2020-Resume-Nursadul-Mamun-July-2020.pdf',
            ],
        ];

        foreach ($documents as $document) {
            $file = $document['file'];
            $source = public_path($file);
            $path = "documents/{$file}";

            Storage::disk('local')->put($path, file_get_contents($source));

            Document::create([
                'title' => $document['title'],
                'short_description' => $document['short_description'],
                'long_description' => $document['long_description'],
                'file_path' => $path,
                'file_name' => $file,
                'file_size' => filesize($source),
            ]);
        }
    }
}
