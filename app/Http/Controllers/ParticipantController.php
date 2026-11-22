<?php

namespace App\Http\Controllers;

use App\Actions\CreateLatecomerAction;
use App\Actions\ImportAttendanceAction;
use App\Actions\ImportParticipantsAction;
use App\Actions\SyncAttendanceFromGoogleSheetAction;
use App\Http\Requests\ImportAttendanceRequest;
use App\Http\Requests\ImportParticipantsRequest;
use App\Http\Requests\StoreLatecomerRequest;
use App\Http\Requests\SyncAttendanceRequest;
use App\Models\AttendanceSetting;
use App\Models\Participant;
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
        $settings = AttendanceSetting::current();

        $participants = Participant::query()
            ->with('draw')
            ->when($filter === 'eligible', fn ($query) => $query->eligible())
            ->when($filter === 'registered', fn ($query) => $query
                ->where('privacy_agreed', true)
                ->where('attended', false)
                ->whereDoesntHave('draw'))
            ->when($filter === 'absent', fn ($query) => $query
                ->where('privacy_agreed', true)
                ->where('attended', false))
            ->when($filter === 'won', fn ($query) => $query->whereHas('draw'))
            ->when($filter === 'declined', fn ($query) => $query->where('privacy_agreed', false))
            ->orderBy('full_name')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Participant $participant): array => [
                'id' => $participant->id,
                'full_name' => $participant->full_name,
                'employee_number' => $participant->employee_number,
                'privacy_agreed' => $participant->privacy_agreed,
                'attended' => $participant->attended,
                'source' => $participant->source->value,
                'has_won' => $participant->draw !== null,
                'status' => $this->statusFor($participant),
            ]);

        return Inertia::render('participants/index', [
            'participants' => $participants,
            'filter' => $filter !== '' ? $filter : 'all',
            'stats' => [
                'total' => Participant::query()->count(),
                'eligible' => Participant::query()->eligible()->count(),
                'registered' => Participant::query()
                    ->where('privacy_agreed', true)
                    ->where('attended', false)
                    ->whereDoesntHave('draw')
                    ->count(),
                'present' => Participant::query()->where('attended', true)->count(),
                'absent' => Participant::query()
                    ->where('privacy_agreed', true)
                    ->where('attended', false)
                    ->count(),
                'won' => Participant::query()->whereHas('draw')->count(),
                'declined' => Participant::query()->where('privacy_agreed', false)->count(),
            ],
            'attendanceSettings' => [
                'spreadsheet_id' => $settings->spreadsheet_id,
                'sheet_range' => $settings->sheet_range,
            ],
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
            ->with('success', 'Latecomer added and is eligible for the next draw.');
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

    public function importAttendance(
        ImportAttendanceRequest $request,
        ImportAttendanceAction $action,
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
                'file' => 'The attendance CSV could not be imported. Please check the file and try again.',
            ]);
        }

        return redirect()
            ->route('participants.index')
            ->with(
                'success',
                "Attendance import: {$result['present']} present, {$result['absent']} absent, {$result['skipped']} skipped.",
            );
    }

    public function syncAttendance(
        SyncAttendanceRequest $request,
        SyncAttendanceFromGoogleSheetAction $action,
    ): RedirectResponse {
        try {
            $result = $action->handle(
                $request->validated('spreadsheet_id'),
                $request->validated('sheet_range'),
            );
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'spreadsheet_id' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'spreadsheet_id' => 'Google Sheet sync failed. Check credentials, sharing, and sheet range.',
            ]);
        }

        return redirect()
            ->route('participants.index')
            ->with(
                'success',
                "Attendance sync: {$result['present']} present, {$result['absent']} absent, {$result['skipped']} skipped.",
            );
    }

    private function statusFor(Participant $participant): string
    {
        if ($participant->draw !== null) {
            return 'won';
        }

        if (! $participant->privacy_agreed) {
            return 'declined';
        }

        if (! $participant->attended) {
            return 'registered';
        }

        return 'eligible';
    }
}
