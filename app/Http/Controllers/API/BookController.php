<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function __construct(private BookService $bookService) {}

    public function index(): JsonResponse
    {
        $books = auth('api')->user()->books()->latest()->paginate(15);

        return response()->json($books);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = $this->bookService->create(auth('api')->user(), $request->validated());

        return response()->json(['message' => 'Book created', 'book' => $book], 201);
    }

    public function show(Book $book): JsonResponse
    {
        $this->authorizeBookAccess($book);

        $book->load('chapters.pages', 'author');

        return response()->json(['book' => $book]);
    }

    public function update(UpdateBookRequest $request, Book $book): JsonResponse
    {
        $this->authorizeBookAccess($book);
        $this->denyIfPublished($book);

        $book = $this->bookService->update($book, $request->validated());

        return response()->json(['message' => 'Book updated', 'book' => $book]);
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->authorizeBookAccess($book);
        $this->denyIfPublished($book);

        $this->bookService->delete($book);

        return response()->json(['message' => 'Book deleted']);
    }

    private function authorizeBookAccess(Book $book): void
    {
        $user = auth('api')->user();

        if ($user->isAuthor() && !$book->isOwnedBy($user->id)) {
            abort(403, 'You do not own this book');
        }
    }

    private function denyIfPublished(Book $book): void
    {
        if ($book->isPublished()) {
            abort(422, 'Published books are read-only');
        }
    }
}
