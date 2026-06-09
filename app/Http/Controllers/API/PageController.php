<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Chapter;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function index(Chapter $chapter): JsonResponse
    {
        $this->authorizeChapter($chapter);

        return response()->json(['pages' => $chapter->pages]);
    }

    public function store(StorePageRequest $request, Chapter $chapter): JsonResponse
    {
        $this->authorizeChapter($chapter);
        $this->denyIfPublished($chapter);

        if ($request->input('order') === null) {
            $request->merge(['order' => $chapter->pages()->max('order') + 1]);
        }

        $page = $chapter->pages()->create($request->validated());

        return response()->json(['message' => 'Page created', 'page' => $page], 201);
    }

    public function update(UpdatePageRequest $request, Page $page): JsonResponse
    {
        $this->authorizeChapter($page->chapter);
        $this->denyIfPublished($page->chapter);

        $page->update($request->validated());

        return response()->json(['message' => 'Page updated', 'page' => $page]);
    }

    public function destroy(Page $page): JsonResponse
    {
        $this->authorizeChapter($page->chapter);
        $this->denyIfPublished($page->chapter);

        $page->delete();

        return response()->json(['message' => 'Page deleted']);
    }

    private function authorizeChapter(Chapter $chapter): void
    {
        $user = auth('api')->user();
        $book = $chapter->book;

        if ($user->isAuthor() && !$book->isOwnedBy($user->id)) {
            abort(403, 'Access denied');
        }
    }

    private function denyIfPublished(Chapter $chapter): void
    {
        if ($chapter->book->isPublished()) {
            abort(422, 'Published books are read-only');
        }
    }
}
