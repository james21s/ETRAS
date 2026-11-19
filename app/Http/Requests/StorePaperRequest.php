<?php

namespace App\Http\Requests;

use App\Models\Paper;
use Illuminate\Foundation\Http\FormRequest;

class StorePaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Paper::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'abstract' => ['required', 'string', 'min:20', 'max:5000'],
            'sectors' => ['required', 'array', 'min:1'],
            'sectors.*' => ['integer', 'distinct', 'exists:sectors,id'],
            'manuscript' => [
                'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf',
                'max:'.config('standardspress.max_upload_kb', 20480),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'manuscript.mimes' => 'The manuscript must be a PDF file.',
            'manuscript.mimetypes' => 'The manuscript must be a PDF file.',
            'manuscript.max' => 'The manuscript may not be larger than '.(config('standardspress.max_upload_kb', 20480) / 1024).' MB.',
        ];
    }
}
