<?php

namespace App\Http\Requests\Request\Api\v1;

use App\Models\Enums\TransactionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort' => ['alpha_dash:ascii', 'nullable'],
            'status' => [
                'nullable',
                Rule::in(
                    array_map(fn (TransactionStatus $status): string => $status->value, TransactionStatus::cases())
                ),
            ],
            'limit' => ['integer', 'min:1', 'max:100'],
            'page' => ['integer', 'min:1'],
        ];
    }
}
