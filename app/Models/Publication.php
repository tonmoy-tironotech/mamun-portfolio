<?php

namespace App\Models;

use App\Support\Html;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'title', 'description'])]
class Publication extends Model
{
    public const TYPES = [
        'journal' => 'Journal Papers',
        'conference' => 'Conference Papers',
        'abstract' => 'Conference Abstracts/Presentations',
    ];

    /**
     * @return HasMany<PublicationFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(PublicationFile::class)->orderBy('id');
    }

    /**
     * @param  Builder<Publication>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        }));
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => Html::clean($this->description),
            'files' => $this->filesArray(),
        ];
    }

    /**
     * PDFs use their own label, or pdf1, pdf2, ... by position when none was given.
     *
     * @return list<array<string, mixed>>
     */
    public function filesArray(): array
    {
        return $this->files->values()->map(fn (PublicationFile $file, int $index) => [
            'id' => $file->id,
            'label' => $file->label ?: 'pdf'.($index + 1),
            'file_name' => $file->file_name,
            'file_size' => $file->file_size,
        ])->all();
    }
}
