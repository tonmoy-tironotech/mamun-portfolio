<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class MemberController extends Controller
{
    private const PER_PAGE = 8;

    public function index(Request $request): Response
    {
        $response = Inertia::render('Members', [
            'current' => $this->section('current', 'current_page'),
            'old' => $this->section('old', 'old_page'),
        ])->toResponse($request);

        // Emails and student IDs are public on this page, but search engines shouldn't list them
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }

    /**
     * One group of students. Each group pages on its own query value, so
     * turning a page in one never moves the other.
     */
    private function section(string $status, string $pageName): LengthAwarePaginator
    {
        return User::approvedStudents()
            ->where('member_status', $status)
            ->orderBy('name')
            ->paginate(self::PER_PAGE, pageName: $pageName)
            ->withQueryString()
            ->through(fn (User $member) => $member->only('id', 'name', 'email', 'student_id', 'education_level', 'profile_photo'));
    }
}
