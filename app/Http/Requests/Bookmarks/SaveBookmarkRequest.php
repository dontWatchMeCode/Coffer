<?php

declare(strict_types=1);

namespace App\Http\Requests\Bookmarks;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveBookmarkRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rulesFor('bookmark', $this->isMethod('patch'));
    }
}
