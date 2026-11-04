<?php

namespace App\Models;

use App\Support\Html;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'image', 'url', 'body'])]
class Project extends Model
{
    /**
     * The only version of the body that may be rendered as HTML.
     *
     * @return Attribute<string, never>
     */
    protected function safeBody(): Attribute
    {
        return Attribute::get(fn () => Html::clean($this->body));
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'image' => $this->image,
            'url' => $this->url,
            'body' => $this->safe_body,
        ];
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'project';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
