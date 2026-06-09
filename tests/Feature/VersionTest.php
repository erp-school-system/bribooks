<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_is_created_on_demand(): void
    {
        $user  = User::factory()->create(['role' => 'author']);
        $book  = Book::factory()->create(['user_id' => $user->id]);
        $token = auth('api')->login($user);

        $chapter = Chapter::factory()->create(['book_id' => $book->id, 'title' => 'Chapter 1']);
        Page::factory()->create(['chapter_id' => $chapter->id, 'content' => '<p>Hello</p>']);

        $response = $this->withToken($token)
            ->postJson("/api/books/{$book->id}/versions", ['label' => 'v1.0']);

        $response->assertStatus(201)
            ->assertJsonPath('version.version_number', 1)
            ->assertJsonPath('version.label', 'v1.0');

        $this->assertDatabaseHas('book_versions', ['book_id' => $book->id, 'version_number' => 1]);
    }

    public function test_snapshot_contains_chapters_and_pages(): void
    {
        $user    = User::factory()->create(['role' => 'author']);
        $book    = Book::factory()->create(['user_id' => $user->id]);
        $token   = auth('api')->login($user);
        $chapter = Chapter::factory()->create(['book_id' => $book->id, 'title' => 'Ch1']);
        Page::factory()->create(['chapter_id' => $chapter->id, 'content' => '<p>page</p>']);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/versions")
            ->assertStatus(201);

        $version = $book->versions()->first();
        $this->assertNotEmpty($version->snapshot['chapters']);
        $this->assertEquals('Ch1', $version->snapshot['chapters'][0]['title']);
        $this->assertNotEmpty($version->snapshot['chapters'][0]['pages']);
    }

    public function test_version_numbers_increment(): void
    {
        $user  = User::factory()->create(['role' => 'author']);
        $book  = Book::factory()->create(['user_id' => $user->id]);
        $token = auth('api')->login($user);

        for ($i = 1; $i <= 3; $i++) {
            $this->withToken($token)
                ->postJson("/api/books/{$book->id}/versions")
                ->assertJsonPath('version.version_number', $i);
        }
    }

    public function test_can_list_versions(): void
    {
        $user  = User::factory()->create(['role' => 'author']);
        $book  = Book::factory()->create(['user_id' => $user->id]);
        $token = auth('api')->login($user);

        $this->withToken($token)->postJson("/api/books/{$book->id}/versions");
        $this->withToken($token)->postJson("/api/books/{$book->id}/versions");

        $this->withToken($token)
            ->getJson("/api/books/{$book->id}/versions")
            ->assertOk()
            ->assertJsonCount(2, 'versions');
    }
}
