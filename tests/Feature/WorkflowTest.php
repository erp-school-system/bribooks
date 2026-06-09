<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuthorWithBook(): array
    {
        $author = User::factory()->create(['role' => 'author']);
        $book   = Book::factory()->create(['user_id' => $author->id, 'status' => 'draft']);
        $token  = auth('api')->login($author);
        return [$author, $book, $token];
    }

    public function test_author_can_submit_book(): void
    {
        [$author, $book, $token] = $this->makeAuthorWithBook();

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/submit")
            ->assertOk()
            ->assertJsonPath('book.status', 'submitted');
    }

    public function test_reviewer_cannot_submit_book(): void
    {
        [, $book] = $this->makeAuthorWithBook();
        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $token    = auth('api')->login($reviewer);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/submit")
            ->assertStatus(403);
    }

    public function test_reviewer_can_approve_submitted_book(): void
    {
        [$author, $book] = $this->makeAuthorWithBook();
        $book->update(['status' => 'submitted']);

        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $token    = auth('api')->login($reviewer);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/approve", ['notes' => 'Looks great'])
            ->assertOk()
            ->assertJsonPath('book.status', 'approved');
    }

    public function test_reviewer_can_reject_book_with_reason(): void
    {
        [$author, $book] = $this->makeAuthorWithBook();
        $book->update(['status' => 'submitted']);

        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $token    = auth('api')->login($reviewer);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/reject", ['reason' => 'Needs more work'])
            ->assertOk()
            ->assertJsonPath('book.status', 'rejected');

        $this->assertDatabaseHas('books', [
            'id'               => $book->id,
            'status'           => 'rejected',
            'rejection_reason' => 'Needs more work',
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        [, $book] = $this->makeAuthorWithBook();
        $book->update(['status' => 'submitted']);

        $reviewer = User::factory()->create(['role' => 'reviewer']);
        $token    = auth('api')->login($reviewer);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_admin_can_publish_approved_book(): void
    {
        [$author, $book] = $this->makeAuthorWithBook();
        $book->update(['status' => 'approved']);

        $admin = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($admin);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/publish")
            ->assertOk()
            ->assertJsonPath('book.status', 'published');
    }

    public function test_author_cannot_publish_book(): void
    {
        [$author, $book, $token] = $this->makeAuthorWithBook();
        $book->update(['status' => 'approved']);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/publish")
            ->assertStatus(403);
    }

    public function test_cannot_publish_non_approved_book(): void
    {
        [$author, $book] = $this->makeAuthorWithBook();

        $admin = User::factory()->create(['role' => 'admin']);
        $token = auth('api')->login($admin);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/publish")
            ->assertStatus(422);
    }

    public function test_rejected_book_can_be_resubmitted(): void
    {
        [$author, $book, $token] = $this->makeAuthorWithBook();
        $book->update(['status' => 'rejected']);

        $this->withToken($token)
            ->postJson("/api/books/{$book->id}/submit")
            ->assertOk()
            ->assertJsonPath('book.status', 'submitted');
    }
}
