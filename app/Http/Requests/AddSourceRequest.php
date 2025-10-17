<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                'max:255',
                Rule::unique('sources', 'feed_url')->where('user_id', auth()->id()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'The website URL is required.',
            'url.url' => 'Please enter a valid URL.',
            'url.unique' => 'You have already added this source.',
        ];
    }
}
