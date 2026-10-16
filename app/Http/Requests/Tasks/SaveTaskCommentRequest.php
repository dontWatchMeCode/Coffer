<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks;

use App\Validation\DomainRecordValidation;
use Illuminate\Foundation\Http\FormRequest;

class SaveTaskCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return DomainRecordValidation::rteBlockRules(true, true);
    }
}
