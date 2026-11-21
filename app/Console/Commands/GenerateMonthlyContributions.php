<?php

namespace App\Console\Commands;

use App\Services\Member\GenerateMonthlyContributions as ContributionGenerator;
use Illuminate\Console\Command;

class GenerateMonthlyContributions extends Command
{
    protected $signature = 'contributions:generate
                            {year? : Contribution year}
                            {month? : Contribution month}';

    protected $description = 'Generate monthly contributions for approved members';

    public function handle(ContributionGenerator $generator): int
    {
        $year = (int) ($this->argument('year') ?? now()->year);
        $month = (int) ($this->argument('month') ?? now()->month);

        try {
            $result = $generator->generate($year, $month);

            $this->info('Monthly contributions generated successfully.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Year', $result['year']],
                    ['Month', $result['month']],
                    ['Total Members', $result['total_members']],
                    ['Created', $result['created']],
                    ['Skipped', $result['skipped']],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
