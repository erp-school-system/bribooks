<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->isAuthor()) {
            return $this->authorDashboard($user);
        }

        if ($user->isReviewer()) {
            return $this->reviewerDashboard();
        }

        return $this->adminDashboard();
    }

    private function authorDashboard($user): JsonResponse
    {
        $books = $user->books()->withCount('chapters')->latest()->get();

        $stats = [
            'total'       => $books->count(),
            'draft'       => $books->where('status', 'draft')->count(),
            'submitted'   => $books->where('status', 'submitted')->count(),
            'under_review'=> $books->where('status', 'under_review')->count(),
            'approved'    => $books->where('status', 'approved')->count(),
            'rejected'    => $books->where('status', 'rejected')->count(),
            'published'   => $books->where('status', 'published')->count(),
        ];

        return response()->json([
            'role'  => 'author',
            'stats' => $stats,
            'books' => $books,
        ]);
    }

    private function reviewerDashboard(): JsonResponse
    {
        $pending = Book::whereIn('status', ['submitted', 'under_review'])
            ->with('author:id,name,email')
            ->latest()
            ->get();

        return response()->json([
            'role'          => 'reviewer',
            'pending_count' => $pending->count(),
            'books'         => $pending,
        ]);
    }

    private function adminDashboard(): JsonResponse
    {
        $stats = [
            'total_books'     => Book::count(),
            'published_books' => Book::where('status', 'published')->count(),
            'pending_review'  => Book::whereIn('status', ['submitted', 'under_review'])->count(),
            'approved'        => Book::where('status', 'approved')->count(),
        ];

        return response()->json([
            'role'  => 'admin',
            'stats' => $stats,
        ]);
    }
}
