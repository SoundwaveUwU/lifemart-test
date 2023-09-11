<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

class ListPossibleIngredientCombinationsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    #[ArrayShape(['ingredients' => "string[]", 'ingredients.*' => "string[]"])]
    public function rules(): array
    {
        return [
            'ingredients' => ['required', 'array'],
            'ingredients.*' => ['required', 'size:1', 'exists:ingredient_type,code'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'ingredients' => str_split($this->input('ingredients')),
        ]);
    }
}
