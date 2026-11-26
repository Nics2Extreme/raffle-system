<?php

namespace App\Console\Commands;

use App\Actions\DrawWinnerAction;
use App\Enums\ParticipantSource;
use App\Enums\PrizeType;
use App\Models\Draw;
use App\Models\Participant;
use App\Models\Prize;
use App\Models\User;
use App\Support\FairDrawSelector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SimulateRaffleRoundsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'raffle:simulate-rounds
                            {--rounds=20 : Number of rounds to simulate}
                            {--winners=15 : Winners drawn per round}
                            {--pool=320 : Eligible employees to seed after clearing}';

    /**
     * @var string
     */
    protected $description = 'Clear draws/participants, seed an eligible pool, run multi-round draws, and print analytics';

    public function handle(DrawWinnerAction $drawWinner): int
    {
        $rounds = max(1, (int) $this->option('rounds'));
        $winnersPerRound = max(1, (int) $this->option('winners'));
        $poolSize = max($rounds * $winnersPerRound, (int) $this->option('pool'));
        $totalDraws = $rounds * $winnersPerRound;

        $this->warn('Clearing draws, prizes, and participants…');
        Draw::query()->delete();
        Prize::query()->delete();
        Participant::query()->delete();

        $this->info("Seeding {$poolSize} eligible employees…");
        $this->seedEligiblePool($poolSize);

        $prize = Prize::query()->create([
            'name' => 'Simulation Prize',
            'quantity' => $totalDraws,
            'remaining' => $totalDraws,
            'type' => PrizeType::Minor,
        ]);

        $operator = User::query()->updateOrCreate(
            ['email' => (string) config('operator.email')],
            [
                'name' => (string) config('operator.name'),
                'password' => Hash::make((string) config('operator.password')),
            ],
        );

        $selector = new FairDrawSelector;
        $roundSummaries = [];
        $indexRatios = [];
        $allDraws = [];

        $bar = $this->output->createProgressBar($totalDraws);
        $bar->start();

        for ($round = 1; $round <= $rounds; $round++) {
            $eligibleBefore = Participant::query()->eligible()->orderBy('employee_number')->get();
            $poolBefore = $selector->describePool($eligibleBefore);
            $roundWinners = [];

            for ($slot = 1; $slot <= $winnersPerRound; $slot++) {
                $draw = $drawWinner->handle($operator, $prize);

                $ratio = $draw->pool_count > 1
                    ? $draw->selected_index / ($draw->pool_count - 1)
                    : 0.0;

                $indexRatios[] = $ratio;
                $entry = [
                    'round' => $round,
                    'slot' => $slot,
                    'employee_number' => $draw->participant->employee_number,
                    'full_name' => $draw->participant->full_name,
                    'selected_index' => $draw->selected_index,
                    'pool_count' => $draw->pool_count,
                    'pool_hash' => $draw->pool_hash,
                    'index_ratio' => $ratio,
                ];
                $roundWinners[] = $entry;
                $allDraws[] = $entry;
                $bar->advance();
            }

            $roundSummaries[] = [
                'round' => $round,
                'pool_count_start' => $poolBefore['pool_count'],
                'pool_hash_start' => $poolBefore['pool_hash'],
                'avg_index_ratio' => collect($roundWinners)->avg('index_ratio'),
                'winners' => $roundWinners,
            ];
        }

        $bar->finish();
        $this->newLine(2);

        $this->renderAnalytics($rounds, $winnersPerRound, $poolSize, $roundSummaries, $indexRatios, $allDraws);

        return self::SUCCESS;
    }

    private function seedEligiblePool(int $count): void
    {
        $rows = [];

        for ($number = 1; $number <= $count; $number++) {
            $rows[] = [
                'full_name' => fake()->name(),
                'employee_number' => str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'privacy_agreed' => true,
                'source' => ParticipantSource::Import->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Participant::query()->insert($chunk);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $roundSummaries
     * @param  list<float>  $indexRatios
     * @param  list<array<string, mixed>>  $allDraws
     */
    private function renderAnalytics(
        int $rounds,
        int $winnersPerRound,
        int $poolSize,
        array $roundSummaries,
        array $indexRatios,
        array $allDraws,
    ): void {
        $ratioMean = array_sum($indexRatios) / max(count($indexRatios), 1);
        $ratioVariance = 0.0;

        foreach ($indexRatios as $ratio) {
            $ratioVariance += ($ratio - $ratioMean) ** 2;
        }

        $ratioVariance /= max(count($indexRatios), 1);
        $ratioStd = sqrt($ratioVariance);

        $lowThird = count(array_filter($indexRatios, fn (float $r): bool => $r < 1 / 3));
        $midThird = count(array_filter($indexRatios, fn (float $r): bool => $r >= 1 / 3 && $r < 2 / 3));
        $highThird = count(array_filter($indexRatios, fn (float $r): bool => $r >= 2 / 3));

        $empNumbers = array_map(fn (array $draw): int => (int) $draw['employee_number'], $allDraws);
        sort($empNumbers);

        $this->info('Simulation complete');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Rounds', (string) $rounds],
                ['Winners / round', (string) $winnersPerRound],
                ['Total winners drawn', (string) count($allDraws)],
                ['Starting pool size', (string) $poolSize],
                ['Remaining eligible', (string) Participant::query()->eligible()->count()],
                ['Draws in DB', (string) Draw::query()->count()],
                ['Algorithm', FairDrawSelector::ALGORITHM],
                ['Mean index ratio (0=first, 1=last)', number_format($ratioMean, 4)],
                ['Std dev of index ratio', number_format($ratioStd, 4)],
                ['Picks in low third of pool', "{$lowThird} (".number_format(100 * $lowThird / count($indexRatios), 1).'%)'],
                ['Picks in mid third of pool', "{$midThird} (".number_format(100 * $midThird / count($indexRatios), 1).'%)'],
                ['Picks in high third of pool', "{$highThird} (".number_format(100 * $highThird / count($indexRatios), 1).'%)'],
                ['Earliest emp no drawn', str_pad((string) $empNumbers[0], 4, '0', STR_PAD_LEFT)],
                ['Latest emp no drawn', str_pad((string) end($empNumbers), 4, '0', STR_PAD_LEFT)],
                ['Median emp no drawn', str_pad((string) $empNumbers[(int) floor((count($empNumbers) - 1) / 2)], 4, '0', STR_PAD_LEFT)],
            ],
        );

        $this->comment('Fair expectation: mean index ratio ≈ 0.50 and thirds each ≈ 33%.');

        $this->newLine();
        $this->info('Per-round summary');
        $this->table(
            ['Round', 'Pool at start', 'Avg index ratio', 'Pool hash (start)'],
            array_map(fn (array $summary): array => [
                $summary['round'],
                $summary['pool_count_start'],
                number_format((float) $summary['avg_index_ratio'], 4),
                substr((string) $summary['pool_hash_start'], 0, 16).'…',
            ], $roundSummaries),
        );

        $this->newLine();
        $this->info('Winners by round');

        foreach ($roundSummaries as $summary) {
            $this->line("Round {$summary['round']}:");
            $this->table(
                ['#', 'Emp No.', 'Name', 'Index', 'Pool'],
                array_map(fn (array $winner): array => [
                    $winner['slot'],
                    $winner['employee_number'],
                    $winner['full_name'],
                    $winner['selected_index'],
                    $winner['pool_count'],
                ], $summary['winners']),
            );
        }
    }
}
