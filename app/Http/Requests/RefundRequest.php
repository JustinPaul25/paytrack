<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefundRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // You can add authorization logic here if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'invoice_id' => ['required', 'exists:invoices,id'],
            'invoice_item_id' => [
                'required', 
                'exists:invoice_items,id',
                Rule::exists('invoice_items', 'id')->where(function ($query) {
                    $query->where('invoice_id', $this->invoice_id);
                })
            ],
            'product_id' => [
                'required', 
                'exists:products,id',
                Rule::exists('invoice_items', 'product_id')->where(function ($query) {
                    $query->where('id', $this->invoice_item_id);
                })
            ],
            'quantity_refunded' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $invoiceItem = \App\Models\InvoiceItem::find($this->invoice_item_id);
                    if ($invoiceItem && $value > $invoiceItem->quantity) {
                        $fail('The quantity refunded cannot exceed the original quantity purchased.');
                    }
                }
            ],
            'refund_amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $invoiceItem = \App\Models\InvoiceItem::find($this->invoice_item_id);
                    if ($invoiceItem) {
                        $maxRefundAmount = ($invoiceItem->price * $this->quantity_refunded);
                        if ($value > $maxRefundAmount) {
                            $fail('The refund amount cannot exceed the total amount for the quantity being refunded.');
                        }
                    }
                }
            ],
            'refund_type' => ['required', 'in:full,partial,exchange'],
            'refund_method' => ['required', 'in:cash,bank_transfer,e-wallet,credit_note,exchange'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
        ];

        // Additional validation for specific refund types
        if ($this->refund_type === 'exchange') {
            $rules['exchange_product_id'] = ['required', 'exists:products,id'];
        }

        if ($this->refund_method === 'bank_transfer') {
            $rules['bank_details'] = ['required', 'string', 'max:500'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Please select an invoice.',
            'invoice_id.exists' => 'The selected invoice does not exist.',
            'invoice_item_id.required' => 'Please select an invoice item.',
            'invoice_item_id.exists' => 'The selected invoice item does not exist.',
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist.',
            'quantity_refunded.required' => 'Please specify the quantity to refund.',
            'quantity_refunded.integer' => 'Quantity must be a whole number.',
            'quantity_refunded.min' => 'Quantity must be at least 1.',
            'refund_amount.required' => 'Please specify the refund amount.',
            'refund_amount.numeric' => 'Refund amount must be a valid number.',
            'refund_amount.min' => 'Refund amount must be greater than 0.',
            'refund_type.required' => 'Please select a refund type.',
            'refund_type.in' => 'Please select a valid refund type.',
            'refund_method.required' => 'Please select a refund method.',
            'refund_method.in' => 'Please select a valid refund method.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'reference_number.max' => 'Reference number cannot exceed 255 characters.',
            'exchange_product_id.required' => 'Please select a product for exchange.',
            'exchange_product_id.exists' => 'The selected exchange product does not exist.',
            'bank_details.required' => 'Bank details are required for bank transfer refunds.',
            'bank_details.max' => 'Bank details cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invoice_id' => 'invoice',
            'invoice_item_id' => 'invoice item',
            'product_id' => 'product',
            'quantity_refunded' => 'quantity refunded',
            'refund_amount' => 'refund amount',
            'refund_type' => 'refund type',
            'refund_method' => 'refund method',
            'reason' => 'reason',
            'notes' => 'notes',
            'reference_number' => 'reference number',
            'exchange_product_id' => 'exchange product',
            'bank_details' => 'bank details',
        ];
    }
}
