<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookmarks;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use Illuminate\Foundation\Http\FormRequest;

class SaveBookmarkRequest extends FormRequest
{
    use AuthorizesTeamResource;

    public function authorize(): bool
    {
        return $this->isTeamMember();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $sometimes = $this->isMethod('patch');

        return [
            'title' => $sometimes
                ? ['sometimes', 'required', 'string', 'max:255']
                : ['required', 'string', 'max:255'],
            'url' => $sometimes
                ? ['sometimes', 'required', 'string', 'url', 'max:2048']
                : ['required', 'string', 'url', 'max:2048'],
            'description' => $sometimes
                ? ['sometimes', 'nullable', 'string', 'max:500']
                : ['nullable', 'string', 'max:500'],
            'notes' => $sometimes
                ? ['sometimes', 'nullable', 'string']
                : ['nullable', 'string'],
        ];
    }
}
