<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Habit\DeleteHabitLogRequest;
use App\Http\Requests\Api\Habit\ListHabitLogsRequest;
use App\Http\Requests\Api\Habit\StoreHabitLogRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HabitLogController extends Controller
{
    use ApiResponse;

    public function store(StoreHabitLogRequest $request, int $id): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->habitNotFoundResponse();
        }

        $loggedDate = Carbon::parse($request->validated('logged_date', Carbon::today()->toDateString()))->toDateString();

        $alreadyLogged = HabitLog::query()
            ->where('habit_id', $habit->id)
            ->whereDate('logged_date', $loggedDate)
            ->exists();

        if ($alreadyLogged) {
            return $this->errorResponse([
                'logged_date' => ['Cette habitude est deja loguee pour cette date.'],
            ], 'Erreur de validation', 422);
        }

        $log = HabitLog::query()->create([
            'habit_id' => $habit->id,
            'user_id' => $request->user()->id,
            'logged_date' => $loggedDate,
            'note' => $request->validated('note'),
        ]);

        return $this->successResponse($log, 'Log ajoute', 201);
    }

    public function index(ListHabitLogsRequest $request, int $id): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->habitNotFoundResponse();
        }

        $query = $habit->logs()->orderByDesc('logged_date');

        if ($request->filled('from')) {
            $query->whereDate('logged_date', '>=', $request->validated('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('logged_date', '<=', $request->validated('to'));
        }

        $logs = $query->paginate($request->integer('per_page', 20));

        return $this->successResponse($logs, 'Historique des logs');
    }

    public function destroy(DeleteHabitLogRequest $request, int $id, int $logId): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->habitNotFoundResponse();
        }

        $log = HabitLog::query()
            ->where('habit_id', $habit->id)
            ->where('user_id', $request->user()->id)
            ->whereKey($logId)
            ->first();

        if (! $log) {
            return $this->errorResponse([
                'log' => ['Log introuvable.'],
            ], 'Introuvable', 404);
        }

        $log->delete();

        return $this->successResponse(null, 'Log supprime');
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
