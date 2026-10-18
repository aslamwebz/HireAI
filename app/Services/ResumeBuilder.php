<?php

namespace App\Services;

use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Response;

class ResumeBuilder
{
    public function generatePdf(Resume $resume): Response
    {
        $template = $this->getTemplateView($resume->template);
        $data = [
            'resume' => $resume,
            'content' => $resume->content,
        ];

        $pdf = DomPDF::loadView($template, $data);
        $pdf->setPaper('a4');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'sans-serif');

        return $pdf->download($this->getFileName($resume));
    }

    public function streamPdf(Resume $resume): Response
    {
        $template = $this->getTemplateView($resume->template);
        $data = [
            'resume' => $resume,
            'content' => $resume->content,
        ];

        $pdf = DomPDF::loadView($template, $data);
        $pdf->setPaper('a4');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'sans-serif');

        return $pdf->stream($this->getFileName($resume));
    }

    public function getPdfContent(Resume $resume): string
    {
        $template = $this->getTemplateView($resume->template);
        $data = [
            'resume' => $resume,
            'content' => $resume->content,
        ];

        $pdf = DomPDF::loadView($template, $data);
        $pdf->setPaper('a4');
        $pdf->setOption('isRemoteEnabled', false);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', 'sans-serif');

        return $pdf->output();
    }

    protected function getTemplateView(string $template): string
    {
        $templates = [
            Resume::TEMPLATE_CLASSIC => 'resumes.templates.classic',
            Resume::TEMPLATE_MODERN => 'resumes.templates.modern',
            Resume::TEMPLATE_CREATIVE => 'resumes.templates.creative',
            Resume::TEMPLATE_MINIMAL => 'resumes.templates.minimal',
            Resume::TEMPLATE_PROFESSIONAL => 'resumes.templates.professional',
        ];

        return $templates[$template] ?? $templates[Resume::TEMPLATE_CLASSIC];
    }

    protected function getFileName(Resume $resume): string
    {
        $name = $resume->getFullName() ?: 'Resume';
        $filename = "{$name} - {$resume->title}.pdf";

        return preg_replace('/[^\w\-\.\s]/', '', $filename);
    }

    public static function getTemplatePreview(string $template): string
    {
        return asset("images/resume-templates/{$template}-preview.png");
    }
}
