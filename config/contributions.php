<?php

return [
    'monthly_amount' => (float) env('MONTHLY_CONTRIBUTION_AMOUNT', 0),

    'due_day' => (int) env('MONTHLY_CONTRIBUTION_DUE_DAY', 15),
];
