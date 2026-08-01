<?php

namespace App\Http\Requests\Assignments;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class SubmissionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(RoleName::Child->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'attachment' => [
                'required',
                'file',
                'max:5120',
                'mimetypes:image/jpeg,image/png,image/webp,audio/mpeg,audio/wav,audio/mp4,application/pdf,text/plain',
                'extensions:jpg,jpeg,png,webp,mp3,wav,m4a,pdf,txt',
            ],
        ];
    }
}
