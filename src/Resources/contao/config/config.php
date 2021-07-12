<?php

$GLOBALS['TL_FFL']['dropzone'] = 'Alnv\FrontendEditingBundle\Form\FormDropzoneUpload';
$GLOBALS['TL_CTE']['frontend_editing-bundle']['frontend_editing'] = 'Alnv\FrontendEditingBundle\Elements\FrontendEditing';

array_insert($GLOBALS['BE_MOD'], 1, [
    'frontend-editing-bundle' => [
        'entities' => [
            'name' => 'entities',
            'tables' => [
                'tl_entity_group',
                'tl_entity',
                'tl_entity_value'
            ]
        ],
        'states' => [
            'name' => 'states',
            'tables' => [
                'tl_states'
            ]
        ]
    ]
]);

$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['FRONTEND-EDITING'] = [
    'feState' => [
        'recipients' => ['admin_email', 'form_*'],
        'email_replyTo' => ['admin_email', 'form_*'],
        'email_sender_name' => ['admin_email', 'form_*'],
        'email_recipient_cc' => ['admin_email', 'form_*'],
        'email_recipient_bcc' => ['admin_email', 'form_*'],
        'email_sender_address' => ['admin_email', 'form_*'],
        'email_subject' => ['admin_email', 'form_*'],
        'attachment_tokens' => ['admin_email', 'form_*'],
        'file_name' => ['admin_email', 'form_*'],
        'file_content' => ['admin_email', 'form_*'],
        'email_text' => ['admin_email', 'form_*'],
        'email_html' => ['admin_email', 'form_*']
    ]
];