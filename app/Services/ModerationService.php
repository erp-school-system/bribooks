<?php

namespace App\Services;

class ModerationService
{
    private array $profanity = [
        'fuck', 'shit', 'asshole', 'bastard', 'bitch', 'cunt', 'damn', 'dick',
        'faggot', 'nigger', 'piss', 'prick', 'slut', 'whore', 'crap',
    ];

    private array $restricted = [
        'buy now', 'click here', 'free money', 'make money fast', 'lose weight fast',
        'casino', 'porn', 'xxx', 'drugs for sale', 'illegal',
    ];

    public function check(string $text): array
    {
        $lower = strtolower($text);
        $flagged = [];

        foreach ($this->profanity as $word) {
            if (str_contains($lower, $word)) {
                $flagged[] = ['type' => 'profanity', 'word' => $word];
            }
        }

        foreach ($this->restricted as $phrase) {
            if (str_contains($lower, $phrase)) {
                $flagged[] = ['type' => 'restricted', 'phrase' => $phrase];
            }
        }

        return $flagged;
    }

    public function checkBook(\App\Models\Book $book): array
    {
        $allText = $book->title . ' ' . $book->description;

        foreach ($book->chapters as $chapter) {
            $allText .= ' ' . $chapter->title;
            foreach ($chapter->pages as $page) {
                $allText .= ' ' . strip_tags($page->content);
            }
        }

        return $this->check($allText);
    }

    public function isPassing(\App\Models\Book $book): bool
    {
        return empty($this->checkBook($book));
    }
}
