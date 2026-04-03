<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('access-domain-assistant');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:12000'],
            'show_all_records' => ['sometimes', 'boolean'],
            'stream' => ['sometimes', 'boolean'],
            'conversation_id' => [
                'nullable',
                'integer',
                Rule::exists('assistant_conversations', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}
