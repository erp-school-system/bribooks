<?php

namespace App\Providers;

use App\Events\BookApproved;
use App\Events\BookCreated;
use App\Events\BookPublished;
use App\Events\BookRejected;
use App\Events\BookSubmitted;
use App\Events\BookVersionCreated;
use App\Events\ModerationPassed;
use App\Listeners\LogBookActivity;
use App\Listeners\SendBookPublishedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $logAll = [
            BookCreated::class,
            BookVersionCreated::class,
            BookSubmitted::class,
            ModerationPassed::class,
            BookApproved::class,
            BookRejected::class,
            BookPublished::class,
        ];

        foreach ($logAll as $event) {
            Event::listen($event, LogBookActivity::class);
        }

        Event::listen(BookPublished::class, SendBookPublishedNotification::class);
    }
}
