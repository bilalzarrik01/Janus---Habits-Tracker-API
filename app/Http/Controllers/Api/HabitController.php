<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Habit\DeleteHabitRequest;
use App\Http\Requests\Api\Habit\ListHabitsRequest;
use App\Http\Requests\Api\Habit\ShowHabitRequest;
use App\Http\Requests\Api\Habit\StoreHabitRequest;
use App\Http\Requests\Api\Habit\UpdateHabitRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Habit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    use ApiResponse;

    public function index(ListHabitsRequest $request): JsonResponse
    {
        $query = $request->user()->habits()->orderByDesc('created_at');

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return $this->successResponse($query->get(), 'Liste des habitudes');
    }

    public function store(StoreHabitRequest $request): JsonResponse
    {
        $habit = $request->user()->habits()->create($request->validated());

        return $this->successResponse($habit, 'Habitude creee', 201);
    }

    public function show(ShowHabitRequest $request, int $id): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->habitNotFoundResponse();
        }

        return $this->successResponse($habit, 'Detail de l\'habitude');
    }

    public function update(UpdateHabitRequest $request, int $id): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->habitNotFoundResponse();
        }

        $habit->update($request->validated());

        return $this->successResponse($habit->fresh(), 'Habitude modifiee');
    }

    public function destroy(DeleteHabitRequest $request, int $id): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->habitNotFoundResponse();
        }

        $habit->delete();

        return $this->successResponse(null, 'Habitude supprimee');
    }

    private function findUserHabit(Request $request, int $habitId): ?Habit
    {
        return Habit::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($habitId)
            ->first();
    }

    private function habitNotFoundResponse(): JsonResponse
    {
        return $this->errorResponse([
            'habit' => ['Habitude introuvable.'],
        ], 'Introuvable', 404);
    }
}
