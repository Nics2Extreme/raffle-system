<?php

namespace App\Actions;

use App\Exceptions\NoEligibleParticipantsException;
use App\Models\Draw;
use App\Models\Participant;
use App\Models\User;
use App\Support\FairDrawSelector;
use Illuminate\Support\Facades\DB;

class DrawWinnerAction
{
    public function __construct(private FairDrawSelector $selector) {}

    public function handle(User $operator, ?string $prizeLabel = null): Draw
    {
        return DB::transaction(function () use ($operator, $prizeLabel): Draw {
            $pool = Participant::query()
                ->eligible()
                ->orderBy('employee_number')
                ->lockForUpdate()
                ->get();

            if ($pool->isEmpty()) {
                throw new NoEligibleParticipantsException;
            }

            $selection = $this->selector->select($pool);

            return Draw::query()->create([
                'participant_id' => $selection['participant']->id,
                'drawn_by' => $operator->id,
                'prize_label' => $prizeLabel !== null && trim($prizeLabel) !== '' ? trim($prizeLabel) : null,
                'algorithm' => $selection['algorithm'],
                'pool_count' => $selection['pool_count'],
                'pool_hash' => $selection['pool_hash'],
                'selected_index' => $selection['selected_index'],
                'drawn_at' => now(),
            ])->load('participant');
        });
    }
}
