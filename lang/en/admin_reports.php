<?php

return [
    'dashboard' => [
        'open_reports' => 'Open reports: :count',
    ],

    'queue' => [
        'title' => 'Reports',
        'heading' => 'Reports',
        'intro' => 'Reports from members about biodatas. The reported member is never told.',
        'tab_open' => 'Open',
        'tab_closed' => 'Closed',
        'empty_open_title' => 'No open reports',
        'empty_open_text' => 'New reports will appear here.',
        'empty_closed_title' => 'No closed reports yet',
        'empty_closed_text' => 'Reports you dismiss or resolve will be listed here.',
        'sent' => 'Sent :time',
        'open_button' => 'Open',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    'review' => [
        'title' => 'Report',
        'back' => 'Back to reports',
        'intro' => 'Read the report and the biodata, then decide. The reporter\'s account details are not shown here.',
        'none' => '—',
        'reported_code' => 'Reported biodata',
        'reporter' => 'Reporter\'s biodata code',
        'reason' => 'Reason',
        'status' => 'Report status',
        'filed' => 'Sent on',
        'other_reports' => 'Reports about this biodata',
        'other_reports_value' => ':total in total, :open open',
        'details' => 'What the reporter wrote',
        'details_none' => 'Nothing written.',
        'biodata_status' => 'Biodata',
        'biodata_gone' => 'The biodata was deleted.',
        'view_biodata' => 'View biodata',
        'handled' => 'Handled on',
        'resolution' => 'Staff note',
        'decision' => 'Your decision',
        'resolution_label' => 'Staff note',
        'resolution_hint' => 'Optional when dismissing. Needed when resolving or hiding (10 to 500 characters). Do not write phone numbers or private details.',
        'dismiss' => 'Dismiss',
        'resolve' => 'Mark as resolved',
        'hide' => 'Hide the biodata and resolve',
        'hide_confirm' => 'Hide this biodata from everyone? Its pending interests will be cancelled and other open reports about it will be resolved.',
        'closed_note' => 'This report is closed.',
    ],

    'errors' => [
        'not_open' => 'This report has already been handled.',
        'resolution_required' => 'Please write a staff note of 10 to 500 characters.',
        'resolution_too_long' => 'Please keep the staff note within 500 characters.',
        'biodata_unavailable' => 'This biodata cannot be hidden because it is not approved or waiting for review. You can mark the report as resolved instead.',
    ],

    'flash' => [
        'dismissed' => 'The report was dismissed.',
        'resolved' => 'The report was marked as resolved.',
        'hidden' => 'The biodata was hidden and the report was resolved.',
    ],
];
