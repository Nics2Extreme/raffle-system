<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Support\FairDrawSelector;
use Illuminate\Console\Command;

class AnalyzeFairDrawCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'raffle:analyze-fairness
                            {--draws=10000 : Number of simulated draws to run}
                            {--show=15 : How many hottest/coldest emp nos to list}';

    /**
     * @var string
     */
    protected $description = 'Simulate many fair draws against the current eligible pool and report distribution stats';

    public function handle(FairDrawSelector $selector): int
    {
        $drawCount = max(1, (int) $this->option('draws'));
        $show = max(1, (int) $this->option('show'));

        $pool = Participant::query()
            ->eligible()
            ->orderBy('employee_number')
            ->get();

        if ($pool->isEmpty()) {
            $this->error('No eligible participants found. Seed the pool first.');

            return self::FAILURE;
        }

        $description = $selector->describePool($pool);
        $n = $description['pool_count'];
        $counts = array_fill(0, $n, 0);

        $this->info("Simulating {$drawCount} draws over {$n} eligible employees…");
        $this->line('Algorithm: '.$description['algorithm']);
        $this->line('Pool hash: '.($description['pool_hash'] ?? '—'));

        $bar = $this->output->createProgressBar($drawCount);
        $bar->start();

        for ($i = 0; $i < $drawCount; $i++) {
            $selection = $selector->select($pool);
            $counts[$selection['selected_index']]++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $expected = $drawCount / $n;
        $chiSquare = 0.0;
        $min = min($counts);
        $max = max($counts);
        $indexed = [];

        foreach ($counts as $index => $hits) {
            $chiSquare += (($hits - $expected) ** 2) / $expected;
            $indexed[] = [
                'index' => $index,
                'employee_number' => $description['employee_numbers'][$index],
                'hits' => $hits,
                'delta' => $hits - $expected,
            ];
        }

        $degreesOfFreedom = $n - 1;
        // Rough critical value reference for df→∞ at α=0.05 is ~chi2 where ratio ≈ 1; report raw stats.
        $mean = array_sum($counts) / $n;
        $variance = 0.0;

        foreach ($counts as $hits) {
            $variance += ($hits - $mean) ** 2;
        }

        $variance /= $n;
        $stdDev = sqrt($variance);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Simulated draws', number_format($drawCount)],
                ['Pool size (n)', number_format($n)],
                ['Expected hits / person', number_format($expected, 4)],
                ['Min hits', (string) $min],
                ['Max hits', (string) $max],
                ['Mean hits', number_format($mean, 4)],
                ['Std deviation', number_format($stdDev, 4)],
                ['Chi-square statistic', number_format($chiSquare, 4)],
                ['Degrees of freedom', (string) $degreesOfFreedom],
                ['Chi-square / df', number_format($chiSquare / $degreesOfFreedom, 4)],
            ],
        );

        $this->comment('For a fair RNG, chi-square/df is typically near 1. Values much larger suggest bias.');

        usort($indexed, fn (array $a, array $b): int => $b['hits'] <=> $a['hits']);
        $hottest = array_slice($indexed, 0, $show);
        $coldest = array_slice(array_reverse($indexed), 0, $show);

        $this->newLine();
        $this->info("Hottest {$show} (most selected):");
        $this->table(
            ['Emp No.', 'Index', 'Hits', 'Δ vs expected'],
            array_map(fn (array $row): array => [
                $row['employee_number'],
                $row['index'],
                $row['hits'],
                number_format($row['delta'], 2),
            ], $hottest),
        );

        $this->info("Coldest {$show} (least selected):");
        $this->table(
            ['Emp No.', 'Index', 'Hits', 'Δ vs expected'],
            array_map(fn (array $row): array => [
                $row['employee_number'],
                $row['index'],
                $row['hits'],
                number_format($row['delta'], 2),
            ], $coldest),
        );

        return self::SUCCESS;
    }
}
