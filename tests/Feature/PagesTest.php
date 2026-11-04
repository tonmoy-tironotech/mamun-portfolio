<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PagesTest extends TestCase
{
    #[TestWith(['/', 'Home'])]
    #[TestWith(['/education', 'Education'])]
    #[TestWith(['/experience', 'Experience'])]
    #[TestWith(['/projects', 'Projects'])]
    #[TestWith(['/publications', 'Publications'])]
    #[TestWith(['/student-corner', 'StudentCorner'])]
    #[TestWith(['/hobbies', 'Hobbies'])]
    #[TestWith(['/contact', 'Contact'])]
    public function test_page_renders_its_inertia_component(string $uri, string $component): void
    {
        $this->withoutVite()
            ->get($uri)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));
    }

    #[TestWith(['/index.html', '/'])]
    #[TestWith(['/Education.html', '/education'])]
    #[TestWith(['/StudentCorner.html', '/student-corner'])]
    public function test_old_html_links_redirect(string $old, string $new): void
    {
        $this->get($old)->assertStatus(301)->assertRedirect($new);
    }
}
