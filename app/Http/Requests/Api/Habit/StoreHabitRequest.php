<?php

namespace App\Http\Requests\Api\Habit;

use App\Http\Requests\Api\ApiFormRequest;

class StoreHabitRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'frequency' => ['required', 'in:daily,weekly,monthly'],
            'target_days' => ['required', 'integer', 'min:1'],
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
