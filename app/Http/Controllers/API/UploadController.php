<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Services\DocumentConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private DocumentConversionService $converter) {}

    public function upload(Request $request, Book $book): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->isAuthor() && !$book->isOwnedBy($user->id)) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        if ($book->isPublished()) {
            return response()->json(['message' => 'Published books are read-only'], 422);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:doc,docx', 'max:20480'],
        ]);

        $file = $request->file('file');
        $path = $file->store('uploads/manuscripts', 'local');
        $fullPath = storage_path("app/{$path}");

        try {
            $htmlPages = $this->converter->convertToPages($fullPath);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Document conversion failed: ' . $e->getMessage(),
            ], 422);
        }

        // Create a new chapter for this uploaded document
        $chapter = $book->chapters()->create([
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'order' => $book->chapters()->max('order') + 1,
        ]);

        foreach ($htmlPages as $index => $html) {
            $chapter->pages()->create([
                'content' => $html,
                'order'   => $index + 1,
            ]);
        }

        return response()->json([
            'message'    => 'Document uploaded and converted',
            'chapter_id' => $chapter->id,
            'pages'      => count($htmlPages),
        ], 201);
    }
}
