<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookmarks;

use App\Http\Requests\Concerns\AuthorizesTeamResource;
use App\Validation\DomainRecordValidation;
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
        return DomainRecordValidation::rulesFor('bookmark', $this->isMethod('patch'));
    }
}
