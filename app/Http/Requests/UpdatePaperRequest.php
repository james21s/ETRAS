<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaperRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('paper')) ?? false;
    }

    public function rules(): array
    {
        return (new StorePaperRequest)->rules();
    }

    public function messages(): array
    {
        return (new StorePaperRequest)->messages();
    }
}
