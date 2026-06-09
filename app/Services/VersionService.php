<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookVersion;

class VersionService
{
    public function snapshot(Book $book): BookVersion
    {
        $book->load('chapters.pages');

        $nextNumber = ($book->versions()->max('version_number') ?? 0) + 1;

        $snapshot = [
            'id'          => $book->id,
            'title'       => $book->title,
            'description' => $book->description,
            'genre'       => $book->genre,
            'status'      => $book->status,
            'chapters'    => $book->chapters->map(function ($chapter) {
                return [
                    'id'    => $chapter->id,
                    'title' => $chapter->title,
                    'order' => $chapter->order,
                    'pages' => $chapter->pages->map(fn($p) => [
                        'id'      => $p->id,
                        'content' => $p->content,
                        'order'   => $p->order,
                    ])->toArray(),
                ];
            })->toArray(),
        ];

        return BookVersion::create([
            'book_id'        => $book->id,
            'version_number' => $nextNumber,
            'snapshot'       => $snapshot,
            'created_at'     => now(),
        ]);
    }

    public function rollback(Book $book, int $versionId): void
    {
        $version = BookVersion::where('book_id', $book->id)
            ->findOrFail($versionId);

        $data = $version->snapshot;

        $book->update([
            'title'       => $data['title'],
            'description' => $data['description'],
            'genre'       => $data['genre'],
        ]);

        $book->chapters()->delete();

        foreach ($data['chapters'] as $chapterData) {
            $chapter = $book->chapters()->create([
                'title' => $chapterData['title'],
                'order' => $chapterData['order'],
            ]);

            foreach ($chapterData['pages'] as $pageData) {
                $chapter->pages()->create([
                    'content' => $pageData['content'],
                    'order'   => $pageData['order'],
                ]);
            }
        }
    }
}
