<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name'     => ['required', 'string', 'max:255'],
            'supplier_tin'      => ['nullable', 'string', 'max:50'],
            'supplier_address'  => ['nullable', 'string', 'max:500'],
            'receipt_number'    => ['nullable', 'string', 'max:100'],
            'date'              => ['required', 'date'],
            'payment_type'      => ['required', 'in:cash,credit'],
            'buyer_name'        => ['nullable', 'string', 'max:255'],
            'vatable_sales'     => ['nullable', 'numeric', 'min:0'],
            'vat_amount'        => ['nullable', 'numeric', 'min:0'],
            'withholding_tax'   => ['nullable', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'items'             => ['required', 'array', 'min:1'],
            'items.*.qty'       => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'      => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.unit_price'  => ['required', 'numeric', 'min:0'],
            'items.*.amount'      => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.required'       => 'Please enter the supplier name.',
            'date.required'                => 'Please select the receipt date.',
            'payment_type.required'        => 'Please select a payment type.',
            'payment_type.in'              => 'Payment type must be cash or credit.',
            'items.required'               => 'At least one line item is required.',
            'items.min'                    => 'At least one line item is required.',
            'items.*.qty.required'         => 'Quantity is required for each item.',
            'items.*.qty.min'              => 'Quantity must be greater than zero.',
            'items.*.description.required' => 'Description is required for each item.',
            'items.*.unit_price.required'  => 'Unit price is required for each item.',
            'items.*.unit_price.min'       => 'Unit price cannot be negative.',
            'items.*.amount.required'      => 'Amount is required for each item.',
            'items.*.amount.min'           => 'Amount cannot be negative.',
        ];
    }
}
