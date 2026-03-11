<?php

namespace App\Http\Requests\Api\Habit;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateHabitRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'frequency' => ['sometimes', 'required', 'in:daily,weekly,monthly'],
            'target_days' => ['sometimes', 'required', 'integer', 'min:1'],
            'color' => ['sometimes', 'nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
