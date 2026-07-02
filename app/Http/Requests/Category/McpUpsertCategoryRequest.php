<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class McpUpsertCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Token ability already enforced by the `mcp.ability:mcp:write` route middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'overwrite_existing' => ['sometimes', 'boolean'],
            'name'               => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order'         => ['sometimes', 'nullable', 'integer', 'min:0'],

            'parent_slug' => [
                'sometimes',
                'nullable',
                'string',
                'exists:categories,slug',
                Rule::notIn([$this->route('slug')]),
            ],

            'translations'                => ['sometimes', 'array', $this->localeKeysRule()],
            'translations.*.name'         => ['nullable', 'string', 'max:255'],
            'translations.*.slug'         => ['nullable', 'string', 'max:300', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'translations.*.description'  => ['nullable', 'string'],
            'translations.*.rich_content' => ['nullable', 'array'],

            'seo'                      => ['sometimes', 'array', $this->localeKeysRule()],
            'seo.*.meta_title'         => ['nullable', 'string', 'max:255'],
            'seo.*.meta_description'   => ['nullable', 'string', 'max:500'],
            'seo.*.og_title'           => ['nullable', 'string', 'max:255'],
            'seo.*.og_description'     => ['nullable', 'string'],
            'seo.*.twitter_title'      => ['nullable', 'string', 'max:255'],
            'seo.*.twitter_description' => ['nullable', 'string'],

            'geo'                     => ['sometimes', 'array', $this->localeKeysRule()],
            'geo.*.ai_summary'        => ['nullable', 'string'],
            'geo.*.use_cases'         => ['nullable', 'string'],
            'geo.*.target_audience'   => ['nullable', 'string'],
            'geo.*.llm_context_hint'  => ['nullable', 'string'],
            'geo.*.key_facts'         => ['nullable', 'array'],
            'geo.*.faq'               => ['nullable', 'array'],
            'geo.*.faq.*.question'    => ['required_with:geo.*.faq', 'string'],
            'geo.*.faq.*.answer'      => ['required_with:geo.*.faq', 'string'],

            'faq_items_vi'            => ['sometimes', 'nullable', 'array'],
            'faq_items_vi.*.question' => ['required_with:faq_items_vi', 'string'],
            'faq_items_vi.*.answer'   => ['required_with:faq_items_vi', 'string'],
            'faq_items_en'            => ['sometimes', 'nullable', 'array'],
            'faq_items_en.*.question' => ['required_with:faq_items_en', 'string'],
            'faq_items_en.*.answer'   => ['required_with:faq_items_en', 'string'],
        ];
    }

    /**
     * Reject any array key that isn't a configured supported locale — blocks
     * junk/typo'd locales (e.g. "vn", "eng") from creating orphan translation rows.
     */
    private function localeKeysRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_array($value)) {
                return;
            }

            $supported = config('app.supported_locales', ['vi', 'en']);
            $invalid   = array_diff(array_keys($value), $supported);

            if (! empty($invalid)) {
                $fail("{$attribute} contains unsupported locale(s): " . implode(', ', $invalid));
            }
        };
    }
}
