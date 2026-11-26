<?php

namespace App\Actions;

use App\Exceptions\NoEligibleParticipantsException;
use App\Exceptions\PrizeUnavailableException;
use App\Models\Draw;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;
use App\Support\FairDrawSelector;
use Illuminate\Support\Facades\DB;

class DrawWinnerAction
{
    public function __construct(private FairDrawSelector $selector) {}

    public function handle(User $operator, Prize $prize): Draw
    {
        return DB::transaction(function () use ($operator, $prize): Draw {
            /** @var Prize|null $lockedPrize */
            $lockedPrize = Prize::query()
                ->whereKey($prize->id)
                ->lockForUpdate()
                ->first();

            if ($lockedPrize === null || $lockedPrize->remaining < 1) {
                throw new PrizeUnavailableException;
            }

            $pool = Participant::query()
                ->eligible()
                ->orderBy('employee_number')
                ->lockForUpdate()
                ->get();

            if ($pool->isEmpty()) {
                throw new NoEligibleParticipantsException;
            }

            $selection = $this->selector->select($pool);

            $draw = Draw::query()->create([
                'participant_id' => $selection['participant']->id,
                'drawn_by' => $operator->id,
                'prize_id' => $lockedPrize->id,
                'prize_label' => $lockedPrize->name,
                'algorithm' => $selection['algorithm'],
                'pool_count' => $selection['pool_count'],
                'pool_hash' => $selection['pool_hash'],
                'selected_index' => $selection['selected_index'],
                'drawn_at' => now(),
            ])->load(['participant', 'prize']);

            $lockedPrize->remaining = max(0, $lockedPrize->remaining - 1);
            $lockedPrize->save();

            return $draw;
        });
    }
}
