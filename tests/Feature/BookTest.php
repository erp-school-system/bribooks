<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAuthor(): array
    {
        $user  = User::factory()->create(['role' => 'author']);
        $token = auth('api')->login($user);
        return [$user, $token];
    }

    public function test_author_can_create_book(): void
    {
        [$user, $token] = $this->actingAsAuthor();

        $response = $this->withToken($token)->postJson('/api/books', [
            'title'       => 'My First Book',
            'description' => 'A great story',
            'genre'       => 'fiction',
        ]);

        $response->assertStatus(201)->assertJsonPath('book.title', 'My First Book');

        $this->assertDatabaseHas('books', ['title' => 'My First Book', 'user_id' => $user->id]);
    }

    public function test_create_book_requires_title(): void
    {
        [, $token] = $this->actingAsAuthor();

        $this->withToken($token)
            ->postJson('/api/books', ['description' => 'no title'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_author_can_list_own_books(): void
    {
        [$user, $token] = $this->actingAsAuthor();
        Book::factory()->count(3)->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->getJson('/api/books')
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_author_can_update_own_book(): void
    {
        [$user, $token] = $this->actingAsAuthor();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->putJson("/api/books/{$book->id}", ['title' => 'Updated Title'])
            ->assertOk()
            ->assertJsonPath('book.title', 'Updated Title');
    }

    public function test_author_cannot_update_another_authors_book(): void
    {
        [, $token] = $this->actingAsAuthor();
        $otherBook = Book::factory()->create();

        $this->withToken($token)
            ->putJson("/api/books/{$otherBook->id}", ['title' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_published_book_is_read_only(): void
    {
        [$user, $token] = $this->actingAsAuthor();
        $book = Book::factory()->published()->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->putJson("/api/books/{$book->id}", ['title' => 'New'])
            ->assertStatus(422);
    }

    public function test_author_can_delete_own_book(): void
    {
        [$user, $token] = $this->actingAsAuthor();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->withToken($token)
            ->deleteJson("/api/books/{$book->id}")
            ->assertOk();

        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }
}
