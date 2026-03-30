<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignProjectAgentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBroker() ?? false;
    }

    public function rules(): array
    {
        return [
            'agent_ids' => ['required', 'array', 'min:1'],
            'agent_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
