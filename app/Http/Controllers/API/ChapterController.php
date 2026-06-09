<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\UpdateChapterRequest;
use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;

class ChapterController extends Controller
{
    public function index(Book $book): JsonResponse
    {
        $this->authorizeBook($book);

        return response()->json(['chapters' => $book->chapters()->with('pages')->get()]);
    }

    public function store(StoreChapterRequest $request, Book $book): JsonResponse
    {
        $this->authorizeBook($book);
        $this->denyIfPublished($book);

        if ($request->input('order') === null) {
            $request->merge(['order' => $book->chapters()->max('order') + 1]);
        }

        $chapter = $book->chapters()->create($request->validated());

        return response()->json(['message' => 'Chapter created', 'chapter' => $chapter], 201);
    }

    public function update(UpdateChapterRequest $request, Chapter $chapter): JsonResponse
    {
        $this->authorizeBook($chapter->book);
        $this->denyIfPublished($chapter->book);

        $chapter->update($request->validated());

        return response()->json(['message' => 'Chapter updated', 'chapter' => $chapter]);
    }

    public function destroy(Chapter $chapter): JsonResponse
    {
        $this->authorizeBook($chapter->book);
        $this->denyIfPublished($chapter->book);

        $chapter->delete();

        return response()->json(['message' => 'Chapter deleted']);
    }

    private function authorizeBook(Book $book): void
    {
        $user = auth('api')->user();

        if ($user->isAuthor() && !$book->isOwnedBy($user->id)) {
            abort(403, 'Access denied');
        }
    }

    private function denyIfPublished(Book $book): void
    {
        if ($book->isPublished()) {
            abort(422, 'Published books are read-only');
        }
    }
}
