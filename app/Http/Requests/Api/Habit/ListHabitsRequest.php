<?php

namespace App\Http\Requests\Api\Habit;

use App\Http\Requests\Api\ApiFormRequest;

class ListHabitsRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'active' => ['nullable', 'boolean'],
        ];
    }
}
