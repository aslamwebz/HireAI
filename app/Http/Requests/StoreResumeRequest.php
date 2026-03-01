<?php

namespace App\Http\Requests;

use App\Models\Resume;
use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'template' => ['nullable', 'string', 'in:'.implode(',', array_keys(Resume::getTemplates()))],
            'content' => ['nullable', 'array'],
            'content.personal' => ['nullable', 'array'],
            'content.professional_summary' => ['nullable', 'string'],
            'content.work_experience' => ['nullable', 'array'],
            'content.education' => ['nullable', 'array'],
            'content.skills' => ['nullable', 'array'],
            'content.certifications' => ['nullable', 'array'],
            'content.languages' => ['nullable', 'array'],
            'content.projects' => ['nullable', 'array'],
            'content.awards' => ['nullable', 'array'],
            'content.publications' => ['nullable', 'array'],
            'content.volunteer_work' => ['nullable', 'array'],
            'content.references' => ['nullable', 'array'],
        ];
    }
}
