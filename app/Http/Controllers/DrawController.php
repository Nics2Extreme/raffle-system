<?php

namespace App\Http\Controllers;

use App\Actions\DrawWinnerAction;
use App\Exceptions\NoEligibleParticipantsException;
use App\Exceptions\PrizeUnavailableException;
use App\Http\Requests\StoreDrawRequest;
use App\Models\Draw;
use App\Models\Participant;
use App\Models\Prize;
use App\Support\FairDrawSelector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DrawController extends Controller
{
    public function index(FairDrawSelector $selector): Response
    {
        $draws = Draw::query()
            ->with(['participant', 'prize'])
            ->latest('drawn_at')
            ->get();

        $eligiblePool = Participant::query()
            ->eligible()
            ->orderBy('employee_number')
            ->get();

        $pool = $selector->describePool($eligiblePool);

        $prizes = Prize::query()
            ->orderByDesc('remaining')
            ->orderByRaw("CASE WHEN type = 'minor' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn (Prize $prize): array => [
                'id' => $prize->id,
                'name' => $prize->name,
                'quantity' => $prize->quantity,
                'remaining' => $prize->remaining,
                'type' => $prize->type->value,
                'available' => $prize->remaining > 0,
            ]);

        $winners = $draws->map(fn (Draw $draw): array => [
            'id' => $draw->id,
            'full_name' => $draw->participant->full_name,
            'employee_number' => $draw->participant->employee_number,
            'prize_label' => $draw->prize_label,
            'prize_type' => $draw->prize?->type->value,
            'drawn_at' => $draw->drawn_at->toIso8601String(),
            'pool_count' => $draw->pool_count,
            'pool_hash' => $draw->pool_hash,
            'selected_index' => $draw->selected_index,
            'algorithm' => $draw->algorithm,
        ])->values()->all();

        $latestDraw = $draws->first();

        return Inertia::render('draw/index', [
            'eligibleCount' => $eligiblePool->count(),
            'winnerCount' => $draws->count(),
            'winners' => $winners,
            'prizes' => $prizes,
            'fairness' => [
                'algorithm' => $pool['algorithm'],
                'pool_count' => $pool['pool_count'],
                'pool_hash' => $pool['pool_hash'],
                'explanation' => 'Eligible employee numbers are sorted ascending. A CSPRNG index (random_int) picks one entry. The pool hash is SHA-256 of those numbers joined by newlines.',
            ],
            'latestWinner' => $latestDraw === null ? null : [
                'full_name' => $latestDraw->participant->full_name,
                'employee_number' => $latestDraw->participant->employee_number,
                'prize_label' => $latestDraw->prize_label,
                'prize_type' => $latestDraw->prize?->type->value,
                'drawn_at' => $latestDraw->drawn_at->toIso8601String(),
                'pool_count' => $latestDraw->pool_count,
                'pool_hash' => $latestDraw->pool_hash,
                'selected_index' => $latestDraw->selected_index,
                'algorithm' => $latestDraw->algorithm,
            ],
        ]);
    }

    public function store(
        StoreDrawRequest $request,
        DrawWinnerAction $action,
    ): RedirectResponse {
        $operator = $request->user();

        abort_unless($operator !== null, 403);

        $prize = Prize::query()->findOrFail($request->integer('prize_id'));

        try {
            $draw = $action->handle($operator, $prize);
        } catch (NoEligibleParticipantsException $exception) {
            throw ValidationException::withMessages([
                'draw' => $exception->getMessage(),
            ]);
        } catch (PrizeUnavailableException $exception) {
            throw ValidationException::withMessages([
                'prize_id' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('draw.index')
            ->with('drawn_winner', [
                'full_name' => $draw->participant->full_name,
                'employee_number' => $draw->participant->employee_number,
                'prize_label' => $draw->prize_label,
                'prize_type' => $draw->prize?->type->value ?? $prize->type->value,
            ]);
    }
}
