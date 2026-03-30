<?php

namespace App\Http\Requests;

use App\Rules\WithinWorkingHours;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleLeadVisitFromConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBuyer() ?? false;
    }

    public function rules(): array
    {
        return [
            'requested_at' => ['required', 'date', 'after:now', new WithinWorkingHours(durationMinutes: 60)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
