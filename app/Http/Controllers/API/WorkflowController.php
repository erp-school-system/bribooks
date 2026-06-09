<?php

namespace App\Http\Controllers\API;

use App\Events\BookApproved;
use App\Events\BookPublished;
use App\Events\BookRejected;
use App\Events\BookSubmitted;
use App\Events\ModerationPassed;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\ModerationService;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private ModerationService $moderation,
        private VersionService $versionService
    ) {}

    public function submit(Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user->isAuthor()) {
            return response()->json(['message' => 'Only authors can submit books'], 403);
        }

        if (!$book->isOwnedBy($user->id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($book->status !== 'draft' && $book->status !== 'rejected') {
            return response()->json(['message' => "Cannot submit a book with status '{$book->status}'"], 422);
        }

        $book->load('chapters.pages');

        $violations = $this->moderation->checkBook($book);

        if (!empty($violations)) {
            return response()->json([
                'message'    => 'Book failed content moderation',
                'violations' => $violations,
            ], 422);
        }

        event(new ModerationPassed($book));

        $this->versionService->snapshot($book);

        $book->update(['status' => 'submitted']);

        event(new BookSubmitted($book));

        return response()->json(['message' => 'Book submitted for review', 'book' => $book]);
    }

    public function approve(Request $request, Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user->isReviewer()) {
            return response()->json(['message' => 'Only reviewers can approve books'], 403);
        }

        if ($book->status !== 'submitted' && $book->status !== 'under_review') {
            return response()->json(['message' => "Cannot approve a book with status '{$book->status}'"], 422);
        }

        $book->reviews()->create([
            'reviewer_id' => $user->id,
            'decision'    => 'approved',
            'notes'       => $request->input('notes'),
        ]);

        $book->update(['status' => 'approved']);

        event(new BookApproved($book));

        return response()->json(['message' => 'Book approved', 'book' => $book]);
    }

    public function reject(Request $request, Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user->isReviewer()) {
            return response()->json(['message' => 'Only reviewers can reject books'], 403);
        }

        $request->validate(['reason' => ['required', 'string']]);

        if ($book->status !== 'submitted' && $book->status !== 'under_review') {
            return response()->json(['message' => "Cannot reject a book with status '{$book->status}'"], 422);
        }

        $book->reviews()->create([
            'reviewer_id' => $user->id,
            'decision'    => 'rejected',
            'notes'       => $request->reason,
        ]);

        $book->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        event(new BookRejected($book, $request->reason));

        return response()->json(['message' => 'Book rejected', 'book' => $book]);
    }

    public function publish(Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Only admins can publish books'], 403);
        }

        if ($book->status !== 'approved') {
            return response()->json(['message' => "Cannot publish a book with status '{$book->status}'"], 422);
        }

        $book->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        event(new BookPublished($book));

        return response()->json(['message' => 'Book published', 'book' => $book]);
    }
}
