<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CvTextExtractor
{
    public function extract(UploadedFile $file): string
    {
        $extension = strtolower($file->extension());
        $path = $file->getRealPath();

        return match ($extension) {
            'txt' => $this->extractFromTxt($path),
            'pdf' => $this->extractFromPdf($path),
            'doc', 'docx' => $this->extractFromDoc($path),
            default => '',
        };
    }

    protected function extractFromTxt(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            Log::warning('CV text extraction failed: could not read TXT file', ['path' => $path]);

            return '';
        }

        return $content;
    }

    protected function extractFromPdf(string $path): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            Log::warning('CV text extraction failed: PDF parser not installed');

            return '';
        }

        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();

            if (empty($text)) {
                Log::warning('CV text extraction: PDF returned empty text', ['path' => $path]);
            }

            return $text ?: '';
        } catch (\Exception $e) {
            Log::error('CV text extraction failed for PDF', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    protected function extractFromDoc(string $path): string
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            Log::warning('CV text extraction failed: PhpWord not installed');

            return '';
        }

        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $textElement) {
                            if (method_exists($textElement, 'getText')) {
                                $text .= $textElement->getText().' ';
                            }
                        }
                    }
                }
            }

            $text = trim($text);

            if (empty($text)) {
                Log::warning('CV text extraction: DOC returned empty text', ['path' => $path]);
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('CV text extraction failed for DOC', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
