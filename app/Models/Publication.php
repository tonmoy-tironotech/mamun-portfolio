<?php

namespace App\Models;

use App\Support\Html;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'title', 'description'])]
class Publication extends Model
{
    public const TYPES = [
        'journal' => 'Journal Papers',
        'conference' => 'Conference Papers',
        'abstract' => 'Conference Abstracts/Presentations',
    ];

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
        ];
    }
}
