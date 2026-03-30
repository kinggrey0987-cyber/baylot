<?php

namespace App\Http\Requests;

use App\Rules\WithinWorkingHours;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isBrokerOrAgent() ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rescheduled,completed,cancelled,rejected'],
            'requested_at' => ['nullable', 'date', new WithinWorkingHours(durationMinutes: 60)],
            'listing_outcome' => ['nullable', 'in:available,reserved'],
            'agent_notes' => ['nullable', 'string', 'max:2000'],
            'down_payment_received' => ['nullable', 'boolean'],
            'sale_confirmed' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('status') === 'rescheduled' && ! $this->filled('requested_at')) {
                $validator->errors()->add('requested_at', 'Reschedule requires a new date and time.');
            }

            if ($this->input('status') === 'completed' && ! $this->filled('listing_outcome')) {
                $validator->errors()->add('listing_outcome', 'Select if the listing is reserved or still available after site visit.');
            }

            if ($this->boolean('down_payment_received') && ! in_array($this->input('status'), ['approved', 'completed'], true)) {
                $validator->errors()->add('down_payment_received', 'Down payment can only be recorded for approved or completed appointments.');
            }

            if ($this->boolean('sale_confirmed') && $this->input('status') !== 'completed') {
                $validator->errors()->add('sale_confirmed', 'Sale can only be confirmed when the appointment is completed.');
            }
        });
    }
}
