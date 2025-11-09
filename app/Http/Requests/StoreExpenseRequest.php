<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'expense_type' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'date' => ['required', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expense_type' => 'expense type',
            'branch_id' => 'branch',
        ];
    }
}
