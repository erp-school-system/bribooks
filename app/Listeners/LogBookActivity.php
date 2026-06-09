<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;

class LogBookActivity
{
    public function handle(object $event): void
    {
        $class = class_basename($event);
        $bookId = $event->book->id ?? '?';

        Log::info("[BriBooks] {$class} fired for book #{$bookId}");
    }
}
