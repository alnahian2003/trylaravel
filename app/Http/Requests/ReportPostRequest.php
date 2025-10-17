<?php

namespace App\Http\Requests;

use App\Enums\ReportType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReportType::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select a report type.',
            'type.enum' => 'Please select a valid report type.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'report type',
        ];
    }
}
