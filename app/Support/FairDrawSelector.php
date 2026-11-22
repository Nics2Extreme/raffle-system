<?php

namespace App\Support;

use App\Exceptions\NoEligibleParticipantsException;
use App\Models\Participant;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FairDrawSelector
{
    public const ALGORITHM = 'sorted-pool-random_int-v1';

    /**
     * @param  (callable(int, int): int)|null  $randomInt
     */
    public function __construct(private $randomInt = null) {}

    /**
     * @param  Collection<int, Participant>|list<Participant>  $eligiblePool
     * @return array{
     *     participant: Participant,
     *     selected_index: int,
     *     pool_count: int,
     *     pool_hash: string,
     *     algorithm: string
     * }
     */
    public function select(Collection|array $eligiblePool): array
    {
        $sorted = $this->sortedPool($eligiblePool);
        $count = $sorted->count();

        if ($count === 0) {
            throw new NoEligibleParticipantsException;
        }

        $selectedIndex = ($this->randomInt ?? random_int(...))(0, $count - 1);

        if ($selectedIndex < 0 || $selectedIndex >= $count) {
            throw new InvalidArgumentException('Selected index is outside the eligible pool.');
        }

        /** @var Participant $participant */
        $participant = $sorted->values()->get($selectedIndex);

        return [
            'participant' => $participant,
            'selected_index' => $selectedIndex,
            'pool_count' => $count,
            'pool_hash' => $this->hashPool($sorted),
            'algorithm' => self::ALGORITHM,
        ];
    }

    /**
     * @param  Collection<int, Participant>|list<Participant>  $eligiblePool
     * @return array{
     *     algorithm: string,
     *     pool_count: int,
     *     pool_hash: string|null,
     *     employee_numbers: array<int, string>
     * }
     */
    public function describePool(Collection|array $eligiblePool): array
    {
        $sorted = $this->sortedPool($eligiblePool);

        return [
            'algorithm' => self::ALGORITHM,
            'pool_count' => $sorted->count(),
            'pool_hash' => $sorted->isEmpty() ? null : $this->hashPool($sorted),
            'employee_numbers' => $sorted
                ->map(fn (Participant $participant): string => $participant->employee_number)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, Participant>|list<Participant>  $eligiblePool
     * @return Collection<int, Participant>
     */
    public function sortedPool(Collection|array $eligiblePool): Collection
    {
        return Collection::make($eligiblePool)
            ->sortBy(fn (Participant $participant): string => $participant->employee_number)
            ->values();
    }

    /**
     * @param  Collection<int, Participant>  $sortedPool
     */
    public function hashPool(Collection $sortedPool): string
    {
        $payload = $sortedPool
            ->map(fn (Participant $participant): string => $participant->employee_number)
            ->implode("\n");

        return hash('sha256', $payload);
    }
}
