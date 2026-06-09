<?php

namespace App\Services;

use PhpOffice\PhpWord\IOFactory;

class DocumentConversionService
{
    // Splits a docx/doc into HTML page chunks (~500 words per page)
    public function convertToPages(string $filePath): array
    {
        $phpWord = IOFactory::load($filePath);
        $html = $this->extractHtml($phpWord);

        return $this->splitIntoPages($html);
    }

    private function extractHtml(\PhpOffice\PhpWord\PhpWord $doc): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'bribooks_') . '.html';

        $writer = IOFactory::createWriter($doc, 'HTML');
        $writer->save($tmpFile);

        $html = file_get_contents($tmpFile);
        unlink($tmpFile);

        // Strip html/body wrapper, keep inner content only
        preg_match('/<body[^>]*>(.*?)<\/body>/si', $html, $m);

        return $m[1] ?? $html;
    }

    private function splitIntoPages(string $html): array
    {
        // Split on paragraph tags so we don't break mid-tag
        $paragraphs = preg_split('/(?=<p[\s>])/i', $html, -1, PREG_SPLIT_NO_EMPTY);

        $pages   = [];
        $current = '';
        $words   = 0;
        $limit   = 500;

        foreach ($paragraphs as $para) {
            $paraWords = str_word_count(strip_tags($para));

            if ($words + $paraWords > $limit && $current !== '') {
                $pages[]  = trim($current);
                $current  = $para;
                $words    = $paraWords;
            } else {
                $current .= $para;
                $words   += $paraWords;
            }
        }

        if (trim($current) !== '') {
            $pages[] = trim($current);
        }

        return $pages ?: ['<p>' . strip_tags($html) . '</p>'];
    }
}
