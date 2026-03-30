<?php

namespace App\Http\Requests;

use App\Rules\WithinWorkingHours;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBuyer() ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:2000'],
            'requested_at' => ['nullable', 'date', 'after:now', new WithinWorkingHours(durationMinutes: 60)],
        ];
    }
}
