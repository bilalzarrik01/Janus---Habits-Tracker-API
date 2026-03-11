<?php

namespace App\Http\Requests\Api\Habit;

use App\Http\Requests\Api\ApiFormRequest;

class StoreHabitLogRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'logged_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
