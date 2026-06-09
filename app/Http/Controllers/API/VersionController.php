<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookVersion;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __construct(private VersionService $versionService) {}

    public function index(Book $book): JsonResponse
    {
        $this->authorizeBook($book);

        $versions = $book->versions()->select(['id', 'version_number', 'label', 'created_at'])->get();

        return response()->json(['versions' => $versions]);
    }

    public function store(Request $request, Book $book): JsonResponse
    {
        $this->authorizeBook($book);

        $request->validate(['label' => ['nullable', 'string', 'max:100']]);

        $version = $this->versionService->snapshot($book);

        if ($request->filled('label')) {
            $version->update(['label' => $request->label]);
        }

        return response()->json(['message' => 'Version snapshot created', 'version' => $version], 201);
    }

    public function show(Book $book, int $versionId): JsonResponse
    {
        $this->authorizeBook($book);

        $version = BookVersion::where('book_id', $book->id)->findOrFail($versionId);

        return response()->json(['version' => $version]);
    }

    public function rollback(Book $book, int $versionId): JsonResponse
    {
        $this->authorizeBook($book);

        if ($book->isPublished()) {
            return response()->json(['message' => 'Cannot rollback a published book'], 422);
        }

        $this->versionService->rollback($book, $versionId);

        return response()->json(['message' => "Book rolled back to version #{$versionId}"]);
    }

    private function authorizeBook(Book $book): void
    {
        $user = auth('api')->user();

        if ($user->isAuthor() && !$book->isOwnedBy($user->id)) {
            abort(403, 'Access denied');
        }
    }
}
