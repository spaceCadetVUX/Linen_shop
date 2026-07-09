<?php

namespace App\Http\Requests\OrderInquiry;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            // Customer-editable note appended to the server-built cart summary —
            // the cart contents themselves are never trusted from the client.
            'message' => ['nullable', 'string', 'max:2000'],
            'channel' => ['required', 'in:zalo,phone,email'],
        ];
    }
}
