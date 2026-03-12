<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Habit\HabitStatsRequest;
use App\Http\Requests\Api\Stats\StatsOverviewRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Habit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StatsController extends Controller
{
    use ApiResponse;

    public function habitStats(HabitStatsRequest $request, int $id): JsonResponse
    {
        $habit = $this->findUserHabit($request, $id);

        if (! $habit) {
            return $this->errorResponse([
                'habit' => ['Habitude introuvable.'],
            ], 'Introuvable', 404);
        }

        $dates = $habit->logs()
            ->orderBy('logged_date')
            ->pluck('logged_date')
            ->map(fn (string $date) => Carbon::parse($date));

        $streaks = $this->calculateStreaks($dates, $habit->frequency);
        $totalCompletions = $habit->logs()->count();
        $completionRate = $this->completionRateForWindow($dates, $habit->frequency, 30);

        return $this->successResponse([
            'habit_id' => $habit->id,
            'current_streak' => $streaks['current_streak'],
            'longest_streak' => $streaks['longest_streak'],
            'total_completions' => $totalCompletions,
            'completion_rate' => $completionRate,
        ], 'Statistiques de l\'habitude');
    }

    public function overview(StatsOverviewRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $activeHabits = $user->habits()->where('is_active', true)->get();

        $today = Carbon::today()->toDateString();

        $completedToday = $activeHabits->filter(function (Habit $habit) use ($today) {
            return $habit->logs()->whereDate('logged_date', $today)->exists();
        })->count();

        $streakByHabit = $activeHabits->map(function (Habit $habit) {
            $dates = $habit->logs()
                ->orderBy('logged_date')
                ->pluck('logged_date')
                ->map(fn (string $date) => Carbon::parse($date));

            return [
                'habit_id' => $habit->id,
                'title' => $habit->title,
                'current_streak' => $this->calculateStreaks($dates, $habit->frequency)['current_streak'],
            ];
        });

        $bestHabit = $streakByHabit->sortByDesc('current_streak')->first();

        $globalRate = $this->globalCompletionRateForHabits($activeHabits, 7);

        return $this->successResponse([
            'total_active_habits' => $activeHabits->count(),
            'completed_today' => $completedToday,
            'habit_with_longest_streak_active' => $bestHabit,
            'global_completion_rate_7_days' => $globalRate,
        ], 'Vue globale des progres');
    }

    private function calculateStreaks(Collection $dates, string $frequency): array
    {
        $periods = $this->toUniquePeriods($dates, $frequency);

        if ($periods->isEmpty()) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
            ];
        }

        $longest = 1;
        $running = 1;

        for ($i = 1; $i < $periods->count(); $i++) {
            $previous = $periods[$i - 1];
            $current = $periods[$i];

            if ($this->isNextPeriod($previous, $current, $frequency)) {
                $running++;
            } else {
                $running = 1;
            }

            $longest = max($longest, $running);
        }

        $lookup = $periods->keyBy(fn (Carbon $period) => $period->toDateString());
        $cursor = $this->normalizePeriod(Carbon::today(), $frequency);
        $currentStreak = 0;

        while ($lookup->has($cursor->toDateString())) {
            $currentStreak++;
            $cursor = $this->previousPeriod($cursor, $frequency);
        }

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longest,
        ];
    }

    private function completionRateForWindow(Collection $dates, string $frequency, int $windowDays): float
    {
        $start = Carbon::today()->subDays($windowDays - 1)->startOfDay();
        $end = Carbon::today()->endOfDay();

        $completedPeriods = $this->toUniquePeriods(
            $dates->filter(fn (Carbon $date) => $date->betweenIncluded($start, $end)),
            $frequency
        )->count();

        $expectedPeriods = $this->expectedPeriodsInWindow($windowDays, $frequency);

        if ($expectedPeriods === 0) {
            return 0.0;
        }

        return round(min(100, ($completedPeriods / $expectedPeriods) * 100), 2);
    }

    private function expectedPeriodsInWindow(int $windowDays, string $frequency): int
    {
        $start = Carbon::today()->subDays($windowDays - 1)->startOfDay();
        $end = Carbon::today()->endOfDay();
        $cursor = $start->copy();
        $keys = [];

        while ($cursor->lte($end)) {
            $keys[$this->normalizePeriod($cursor, $frequency)->toDateString()] = true;
            $cursor->addDay();
        }

        return count($keys);
    }

    private function globalCompletionRateForHabits(Collection $habits, int $windowDays): float
    {
        if ($habits->isEmpty()) {
            return 0.0;
        }

        $expected = 0;
        $completed = 0;
        $start = Carbon::today()->subDays($windowDays - 1)->startOfDay();
        $end = Carbon::today()->endOfDay();

        /** @var Habit $habit */
        foreach ($habits as $habit) {
            $dates = $habit->logs()
                ->pluck('logged_date')
                ->map(fn (string $date) => Carbon::parse($date))
                ->filter(fn (Carbon $date) => $date->betweenIncluded($start, $end));

            $completed += $this->toUniquePeriods($dates, $habit->frequency)->count();
            $expected += $this->expectedPeriodsInWindow($windowDays, $habit->frequency);
        }

        if ($expected === 0) {
            return 0.0;
        }

        return round(min(100, ($completed / $expected) * 100), 2);
    }

    private function toUniquePeriods(Collection $dates, string $frequency): Collection
    {
        return $dates
            ->map(fn (Carbon $date) => $this->normalizePeriod($date, $frequency))
            ->unique(fn (Carbon $period) => $period->toDateString())
            ->sortBy(fn (Carbon $period) => $period->timestamp)
            ->values();
    }

    private function normalizePeriod(Carbon $date, string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => $date->copy()->startOfWeek(),
            'monthly' => $date->copy()->startOfMonth(),
            default => $date->copy()->startOfDay(),
        };
    }

    private function previousPeriod(Carbon $period, string $frequency): Carbon
    {
        return match ($frequency) {
            'weekly' => $period->copy()->subWeek(),
            'monthly' => $period->copy()->subMonth(),
            default => $period->copy()->subDay(),
        };
    }

    private function isNextPeriod(Carbon $previous, Carbon $current, string $frequency): bool
    {
        return match ($frequency) {
            'weekly' => $previous->copy()->addWeek()->equalTo($current),
            'monthly' => $previous->copy()->addMonth()->equalTo($current),
            default => $previous->copy()->addDay()->equalTo($current),
        };
    }

    private function findUserHabit(Request $request, int $habitId): ?Habit
    {
        return Habit::query()
            ->where('user_id', $request->user()->id)
            ->whereKey($habitId)
            ->first();
    }
}
