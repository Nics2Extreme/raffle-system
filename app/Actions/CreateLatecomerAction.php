<?php

namespace App\Actions;

use App\Enums\ParticipantSource;
use App\Models\Participant;
use App\Support\EmployeeNumber;
use Illuminate\Validation\ValidationException;

class CreateLatecomerAction
{
    /**
     * @param  array{full_name: string, employee_number: string}  $data
     */
    public function handle(array $data): Participant
    {
        $employeeNumber = EmployeeNumber::normalize($data['employee_number']);

        if (Participant::query()->where('employee_number', $employeeNumber)->exists()) {
            throw ValidationException::withMessages([
                'employee_number' => 'This employee number is already registered.',
            ]);
        }

        return Participant::query()->create([
            'full_name' => $data['full_name'],
            'employee_number' => $employeeNumber,
            'privacy_agreed' => true,
            'attended' => true,
            'attended_at' => now(),
            'source' => ParticipantSource::Manual,
        ]);
    }
}
