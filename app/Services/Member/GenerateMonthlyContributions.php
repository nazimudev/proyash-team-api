<?php

namespace App\Services\Member;

use App\Models\Member;
use App\Models\MemberContributionSetting;
use App\Models\MonthlyContribution;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateMonthlyContributions
{
    public function generate(int $year, int $month, ?int $createdBy = null): array
    {
        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'month' => 'Month must be between 1 and 12.',
            ]);
        }

        $amount = (float) config('contributions.monthly_amount');

        if ($amount <= 0) {
            throw new \RuntimeException(
                'Monthly contribution amount is not configured.'
            );
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $dueDay = (int) config('contributions.due_day', 10);

        $dueDate = $periodStart->copy()->day(
            min($dueDay, $periodEnd->day)
        );

        return DB::transaction(function () use (
            $year,
            $month,
            $amount,
            $periodStart,
            $periodEnd,
            $dueDate,
            $createdBy
        ) {
            $members = Member::query()
                ->where('status', 'approved')
                ->whereHas('user', function ($query) {
                    $query->where('is_active', true);
                })
                ->get();

            $created = 0;
            $skipped = 0;

            foreach ($members as $member) {
                // Find the contribution rate effective for this month.
                $setting = MemberContributionSetting::query()
                    ->where('member_id', $member->id)
                    ->whereDate('effective_from', '<=', $periodEnd)
                    ->where(function ($query) use ($periodStart) {
                        $query->whereNull('effective_until')
                            ->orWhereDate(
                                'effective_until',
                                '>=',
                                $periodStart
                            );
                    })
                    ->orderByDesc('effective_from')
                    ->first();

                // If no rate exists, create the common fixed rate.
                if (!$setting) {
                    $setting = MemberContributionSetting::create([
                        'member_id' => $member->id,
                        'monthly_amount' => $amount,
                        'effective_from' => $periodStart->toDateString(),
                        'effective_until' => null,
                        'created_by' => $createdBy,
                    ]);
                }

                // Unique member + year + month prevents duplicates.
                $contribution = MonthlyContribution::firstOrCreate(
                    [
                        'member_id' => $member->id,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'required_amount' => $setting->monthly_amount,
                        'waived_amount' => 0,
                        'due_date' => $dueDate->toDateString(),
                    ]
                );

                if ($contribution->wasRecentlyCreated) {
                    $created++;
                } else {
                    $skipped++;
                }
            }

            return [
                'year' => $year,
                'month' => $month,
                'total_members' => $members->count(),
                'created' => $created,
                'skipped' => $skipped,
            ];
        });
    }
}
