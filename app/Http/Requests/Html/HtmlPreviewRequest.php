<?php

namespace App\Http\Requests\Html;

use Illuminate\Foundation\Http\FormRequest;

class HtmlPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['source_html' => ['present', 'string', 'max:8000']];
    }
}
