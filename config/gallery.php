<?php

return [

    'categories' => [
        'Education & Awareness',
        'Healthcare',
        'Humanitarian Assistance',
        'Environmental',
        'Youth Development',
        'Equality & Social Inclusion',
        'Others',
    ],

    'statuses' => [
        'draft',
        'published',
    ],

    'image' => [
        'disk' => 'public',
        'directory' => 'gallery',
        'max_size' => 5120, // KB
        'allowed_types' => [
            'jpg',
            'jpeg',
            'png',
            'webp',
        ],
    ],

];
