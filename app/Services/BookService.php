<?php

namespace App\Services;

use App\Events\BookCreated;
use App\Models\Book;
use App\Models\User;

class BookService
{
    public function __construct(private VersionService $versionService) {}

    public function create(User $author, array $data): Book
    {
        $book = $author->books()->create($data);

        event(new BookCreated($book));

        return $book;
    }

    public function update(Book $book, array $data): Book
    {
        // Snapshot before significant update
        $this->versionService->snapshot($book);

        $book->update($data);

        return $book->fresh();
    }

    public function delete(Book $book): void
    {
        $book->delete();
    }
}
