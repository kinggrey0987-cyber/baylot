<?php

namespace App\Rules;

use Carbon\CarbonInterface;
use Closure;
use DateTimeInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

class WithinWorkingHours implements ValidationRule
{
    public function __construct(
        private readonly int $startHour = 8,
        private readonly int $endHour = 17,
        private readonly int $durationMinutes = 0,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $dateTime = $this->parseDateTime($value);

        if (! $dateTime) {
            return;
        }

        $startBoundary = $dateTime->copy()->setTime($this->startHour, 0, 0);
        $endBoundary = $dateTime->copy()->setTime($this->endHour, 0, 0);
        $latestStartBoundary = $this->durationMinutes > 0
            ? $endBoundary->copy()->subMinutes($this->durationMinutes)
            : $endBoundary;

        if ($dateTime->lt($startBoundary) || $dateTime->gt($latestStartBoundary)) {
            $startLabel = $startBoundary->format('g:i A');
            $endLabel = $endBoundary->format('g:i A');

            if ($this->durationMinutes > 0) {
                $latestStartLabel = $latestStartBoundary->format('g:i A');

                $fail("The :attribute must start between {$startLabel} and {$latestStartLabel} to stay within working hours ({$startLabel} to {$endLabel}).");

                return;
            }

            $fail("The :attribute must be scheduled between {$startLabel} and {$endLabel}.");
        }
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (! is_scalar($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
