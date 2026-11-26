<?php

namespace App\Http\Controllers;

use App\Actions\CreateLatecomerAction;
use App\Actions\ImportParticipantsAction;
use App\Actions\ImportPrizesAction;
use App\Http\Requests\ImportParticipantsRequest;
use App\Http\Requests\ImportPrizesRequest;
use App\Http\Requests\StoreLatecomerRequest;
use App\Models\Participant;
use App\Models\Prize;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

class ParticipantController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->string('filter')->toString();

        $participants = Participant::query()
            ->with('draw')
            ->when($filter === 'eligible', fn ($query) => $query->eligible())
            ->when($filter === 'won', fn ($query) => $query->whereHas('draw'))
            ->orderBy('full_name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Participant $participant): array => [
                'id' => $participant->id,
                'full_name' => $participant->full_name,
                'employee_number' => $participant->employee_number,
                'privacy_agreed' => $participant->privacy_agreed,
                'source' => $participant->source->value,
                'has_won' => $participant->draw !== null,
                'status' => $this->statusFor($participant),
            ]);

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
            ]);

        return Inertia::render('participants/index', [
            'participants' => $participants,
            'filter' => $filter !== '' ? $filter : 'all',
            'stats' => [
                'total' => Participant::query()->count(),
                'eligible' => Participant::query()->eligible()->count(),
                'won' => Participant::query()->whereHas('draw')->count(),
            ],
            'prizes' => $prizes,
        ]);
    }

    public function store(
        StoreLatecomerRequest $request,
        CreateLatecomerAction $action,
    ): RedirectResponse {
        try {
            $action->handle([
                'full_name' => $request->string('full_name')->toString(),
                'employee_number' => $request->string('employee_number')->toString(),
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'employee_number' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('participants.index')
            ->with('success', 'Participant added and is eligible for the next draw.');
    }

    public function import(
        ImportParticipantsRequest $request,
        ImportParticipantsAction $action,
    ): RedirectResponse {
        try {
            $result = $action->handle($request->file('file'));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'The CSV could not be imported. Please check the file and try again.',
            ]);
        }

        return redirect()
            ->route('participants.index')
            ->with(
                'success',
                "Import complete: {$result['imported']} added, {$result['updated']} updated, {$result['skipped']} skipped.",
            );
    }

    public function importPrizes(
        ImportPrizesRequest $request,
        ImportPrizesAction $action,
    ): RedirectResponse {
        try {
            $result = $action->handle($request->file('file'));
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => 'The prizes spreadsheet could not be imported. Please check the file and try again.',
            ]);
        }

        return redirect()
            ->route('participants.index')
            ->with(
                'success',
                "Prize import: {$result['imported']} added, {$result['updated']} updated, {$result['skipped']} skipped.",
            );
    }

    private function statusFor(Participant $participant): string
    {
        if ($participant->draw !== null) {
            return 'won';
        }

        return 'eligible';
    }
}
