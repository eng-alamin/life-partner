<?php

return [
    'slugs' => [
        'about' => 'About us',
        'how-it-works' => 'How it works',
        'faq' => 'FAQ',
        'contact' => 'Contact us',
        'privacy' => 'Privacy policy',
        'terms' => 'Terms of use',
        'refund' => 'Refund policy',
    ],

    'list' => [
        'title' => 'Content pages',
        'heading' => 'Content pages',
        'intro' => 'The words of these pages can be changed. New pages can not be added or deleted. An unpublished page shows a 404 to everyone and has no menu link.',
        'published' => 'Published',
        'unpublished' => 'Unpublished',
        'updated' => 'Last changed: :when',
        'not_saved' => 'Not saved yet',
        'view' => 'View',
        'edit' => 'Edit',
    ],

    'form' => [
        'title' => 'Edit page',
        'heading' => 'Edit :page',
        'back' => 'Back to the page list',
        'title_bn' => 'Title (Bangla)',
        'title_en' => 'Title (English)',
        'body_bn' => 'Text (Bangla)',
        'body_en' => 'Text (English)',
        'body_hint' => 'Plain text. Use ## for a large heading, ### for a small heading, - for a list and **like this** for bold. A blank line starts a new paragraph. HTML and links do not work.',
        'body_en_hint' => 'If left empty, readers in English see the Bangla text.',
        'published_label' => 'Publish this page',
        'published_hint' => 'When published, everyone can see the page and it is linked in the menu and footer. A Bangla text is needed to publish.',
        'save' => 'Save',
        'legal_note' => 'This is a legal page. The seeded text is only a first draft. Have a lawyer read it before you publish, and change it to match your real rules.',
        'contact_note' => 'The contact email, phone and address are changed on the Settings page and are shown under this page automatically. Before publishing, add your real contact details there.',
    ],

    'flash' => [
        'saved' => 'The page has been saved.',
    ],

    'errors' => [
        'title_invalid' => 'Enter a title (at most :max characters).',
        'body_invalid' => 'The text is not valid or is longer than :max characters.',
        'publish_needs_bangla' => 'A Bangla text is required to publish.',
    ],
];
