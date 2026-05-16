<?php

return [
    // Country code that represents ASEANstats (Admin)
    'admin_country_code' => env('OPENDATA_ADMIN_COUNTRY_CODE', '00'),

    // Optional: section_id used to store weighted summary in od_trx_assessment_summaries.
    // If null, weighted summary will be computed on the fly and NOT persisted.
    'weighted_section_id' => env('OPENDATA_WEIGHTED_SECTION_ID'),

    // Audit log event name
    'audit_event' => env('OPENDATA_AUDIT_EVENT', 'opendata'),

    // Password reset token validity (minutes)
    'password_reset_expire_minutes' => (int) env('OPENDATA_PASSWORD_RESET_EXPIRE', 5),

    // Temporary switch: allow non-ASEANstats users to export assessment data.
    'allow_export' => (bool) env('OPENDATA_ALLOW_EXPORT', false),
];
