<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

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
        return file_get_contents($path) ?: '';
    }

    protected function extractFromPdf(string $path): string
    {
        if (! class_exists(\Smalot\PdfParser\Parser::class)) {
            return '';
        }

        try {
            $parser = new \Smalot\PdfParser\Parser;
            $pdf = $parser->parseFile($path);

            return $pdf->getText() ?: '';
        } catch (\Exception) {
            return '';
        }
    }

    protected function extractFromDoc(string $path): string
    {
        if (! class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
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

            return trim($text);
        } catch (\Exception) {
            return '';
        }
    }
}
