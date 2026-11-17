<?php

// Fixed choices used by forms and filters. Change them here and every dropdown follows.
return [
    'programs' => ['BSIT', 'BSCS'],

    'categories' => [
        'Information System',
        'Web Application',
        'Mobile Application',
        'IoT / Embedded',
        'Game Development',
        'Data Science / AI',
    ],

    'academic_years' => ['2026–2027', '2025–2026', '2024–2025', '2023–2024', '2022–2023'],

    'statuses' => [
        'draft' => 'Draft',
        'pending' => 'Pending Review',
        'returned' => 'Resubmission Required',
        'approved' => 'Approved',
    ],

    'roles' => [
        'student' => 'Student',
        'coordinator' => 'Research Coordinator',
        'administrator' => 'Administrator',
    ],

    // Accepted uploads (max 50 MB each).
    'file_types' => 'pdf,doc,docx,ppt,pptx,zip',
];
