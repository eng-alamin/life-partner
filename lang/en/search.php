<?php

return [
    'title' => 'Search biodatas',
    'heading' => 'Search biodatas',
    'intro_guest' => 'Browse approved biodatas. Register to see full profiles and use all filters.',
    'intro_member' => 'Biodatas that match what you are looking for. Only approved biodatas appear here.',

    'fields' => [
        'type' => 'Looking for',
        'age_min' => 'Minimum age',
        'age_max' => 'Maximum age',
        'height_min' => 'Minimum height (cm)',
        'height_max' => 'Maximum height (cm)',
        'marital_status' => 'Marital status',
        'country' => 'Country of residence',
        'district' => 'Present district',
        'min_education' => 'Minimum education',
        'profession' => 'Profession',
        'practicing_level' => 'Minimum practicing level',
    ],

    'hints' => [
        'profession' => 'Part of the profession is enough, for example teacher.',
    ],

    'apply' => 'Search',
    'clear' => 'Clear filters',
    'previous' => 'Previous',
    'next' => 'Next',
    'page' => 'Page :page',

    'card' => [
        'age' => 'Age :age',
        'view' => 'View profile',
    ],

    'empty' => [
        'title' => 'No biodatas found',
        'text' => 'Try fewer filters or a wider age range.',
    ],

    'choose_type' => [
        'title' => 'Choose who you are looking for',
        'text' => 'Select groom or bride above, then press Search.',
    ],

    'need_biodata' => [
        'title' => 'Start your biodata first',
        'text' => 'We show you the biodatas that match your own, so we need to know whether your biodata is for a groom or a bride.',
        'button' => 'Go to my biodata',
    ],

    'paused' => [
        'title' => 'Your biodata is paused',
        'text' => 'While your biodata is hidden or deactivated, or your account deletion is waiting, you cannot browse other members. Open your biodata to see what you can do.',
        'button' => 'Go to my biodata',
    ],

    'guest_limit' => [
        'title' => 'Want to see more?',
        'text' => 'Guests can browse the first three pages. Register or log in to see every biodata and all filters.',
    ],

    'saved' => [
        'heading' => 'Saved searches',
        'name_label' => 'Name for this search',
        'name_hint' => 'Saves the filters above. You can keep up to 10.',
        'save' => 'Save this search',
        'empty' => 'You have no saved searches yet.',
        'load' => 'Use',
        'delete' => 'Delete',
        'confirm_delete' => 'Delete this saved search?',
        'limit' => 'You can keep up to :max saved searches. Delete one first.',
        'duplicate' => 'You already have a saved search with this name.',
        'no_filters' => 'Choose at least one filter before saving.',
    ],

    'profile' => [
        'title' => 'Biodata :code',
        'heading' => 'Biodata',
        'back' => 'Back to search',
        'own_note' => 'This is how other members see your biodata.',
        'sections' => [
            'general' => 'General',
            'education' => 'Education',
            'work' => 'Work',
            'religion' => 'Religious life',
            'family' => 'Family',
            'personal' => 'About',
            'marriage' => 'Marriage plans',
            'preferences' => 'Partner preferences',
        ],
    ],
];
