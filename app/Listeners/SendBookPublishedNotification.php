<?php

namespace App\Listeners;

use App\Events\BookPublished;
use Illuminate\Support\Facades\Log;

class SendBookPublishedNotification
{
    public function handle(BookPublished $event): void
    {
        // In a real system this would queue an email to the author.
        Log::info("[BriBooks] Book #{$event->book->id} published — notification queued for {$event->book->author->email}");
    }
}
