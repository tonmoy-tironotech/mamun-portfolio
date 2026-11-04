<?php

namespace App\Models;

use App\Support\Html;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'short_description', 'long_description', 'file_path', 'file_name', 'file_size'])]
#[Hidden(['file_path'])]
class Document extends Model
{
    /**
     * @param  Builder<Document>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->when($term !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%");
        }));
    }

    /**
     * For tables: the short description as plain text.
     *
     * @return array<string, mixed>
     */
    public function toListArray(): array
    {
        return [
            ...$this->only('id', 'title', 'file_name', 'file_size'),
            'short_description' => Html::text($this->short_description),
        ];
    }

    /**
     * For pages and the edit form: descriptions as sanitized HTML.
     *
     * @return array<string, mixed>
     */
    public function toDetailArray(): array
    {
        return [
            ...$this->only('id', 'title', 'file_name', 'file_size'),
            'short_description' => Html::clean($this->short_description),
            'long_description' => Html::clean($this->long_description),
        ];
    }
}
