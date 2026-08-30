<?php

return [
    'install' => [
        [
            'table' => 'auth_permissions',
            'rows'  => [
                ['alias' => 'forms.manage', 'description' => 'Create, edit, and delete forms and review submissions'],
            ],
        ],
        [
            'table' => 'forms',
            'rows'  => [
                [
                    'name'            => 'Contact',
                    'slug'            => 'contact',
                    'description'     => 'A basic contact form.',
                    'status'          => 'draft',
                    'submit_label'    => 'Send',
                    'success_message' => 'Thanks, your message has been received.',
                ],
            ],
        ],
        [
            'table' => 'form_fields',
            'rows'  => [
                ['form_id' => 1, 'type' => 'text', 'name' => 'name', 'label' => 'Name', 'is_required' => 1, 'width' => 'full', 'sort_order' => 1],
                ['form_id' => 1, 'type' => 'email', 'name' => 'email', 'label' => 'Email', 'is_required' => 1, 'width' => 'full', 'sort_order' => 2],
                ['form_id' => 1, 'type' => 'textarea', 'name' => 'message', 'label' => 'Message', 'is_required' => 1, 'width' => 'full', 'sort_order' => 3],
            ],
        ],
    ],
];
