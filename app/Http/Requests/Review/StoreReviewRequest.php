<?php

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Guest reviews allowed — no storefront login exists yet.
            // author/email always come from the form, even for logged-in users.
            'author' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:3', 'max:2000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:4096'],
        ];
    }
}
