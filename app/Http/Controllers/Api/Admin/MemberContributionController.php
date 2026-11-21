<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Member\GenerateMonthlyContributions;
use Illuminate\Http\Request;

class MemberContributionController extends Controller
{
    public function generate(
        Request $request,
        GenerateMonthlyContributions $generator
    ) {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $result = $generator->generate(
            (int) $validated['year'],
            (int) $validated['month'],
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Monthly contributions generated successfully.',
            'data' => $result,
        ]);
    }
}
