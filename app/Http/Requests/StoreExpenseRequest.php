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
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the amount spent.',
            'amount.numeric' => 'Amount must be a number.',
            'amount.min' => 'Amount can’t be negative.',
            'amount.max' => 'Amount is too large.',

            'expense_type.required' => 'Please select the expense type.',
            'expense_type.max' => 'Expense type is too long.',

            'description.max' => 'Description is too long (max 1000 characters).',

            'date.required' => 'Please pick a date.',
            'date.date' => 'Please enter a valid date.',

            'branch_id.exists' => 'Please select a valid branch.',
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
