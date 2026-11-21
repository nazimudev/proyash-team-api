<?php

return [

    'categories' => [
        'Community Support',
        'Tree Plantation',
        'Education Support',
        'Social Activities',
        'Events',
        'Other Activities',
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
