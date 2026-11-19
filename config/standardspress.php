<?php

return [
    // Maximum manuscript size in kilobytes (validated server-side).
    'max_upload_kb' => 20480,

    // Oversight thresholds (days).
    'review_overdue_days' => 14,
    'paper_overdue_days' => 30,

    // Prefix of the public reference assigned on publication.
    'reference_prefix' => 'STD',

    // Filesystem disk that stores manuscripts (private, never web-accessible).
    'disk' => 'local',
];
