<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_with_profanity_fails_submission(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $book   = Book::factory()->create([
            'user_id' => $author->id,
            'status'  => 'draft',
            'title'   => 'A shit book',
        ]);
        $token  = auth('api')->login($author);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/submit")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Book failed content moderation')
            ->assertJsonStructure(['violations']);
    }

    public function test_book_with_restricted_phrase_fails_submission(): void
    {
        $author  = User::factory()->create(['role' => 'author']);
        $book    = Book::factory()->create(['user_id' => $author->id, 'status' => 'draft']);
        $chapter = Chapter::factory()->create(['book_id' => $book->id]);
        Page::factory()->create([
            'chapter_id' => $chapter->id,
            'content'    => '<p>buy now and casino here</p>',
        ]);
        $token = auth('api')->login($author);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/submit")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Book failed content moderation');
    }

    public function test_clean_book_passes_moderation(): void
    {
        $author  = User::factory()->create(['role' => 'author']);
        $book    = Book::factory()->create(['user_id' => $author->id, 'status' => 'draft', 'title' => 'My Clean Book']);
        $chapter = Chapter::factory()->create(['book_id' => $book->id]);
        Page::factory()->create([
            'chapter_id' => $chapter->id,
            'content'    => '<p>Once upon a time there was a kind dragon.</p>',
        ]);
        $token = auth('api')->login($author);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/submit")
            ->assertOk()
            ->assertJsonPath('book.status', 'submitted');
    }
}
