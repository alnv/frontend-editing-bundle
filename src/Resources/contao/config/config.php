<?php

use Contao\ArrayUtil;
use Alnv\FrontendEditingBundle\Elements\FrontendEditing;
use Alnv\FrontendEditingBundle\Form\FormDropzoneUpload;

$GLOBALS['TL_FFL']['dropzone'] = FormDropzoneUpload::class;
$GLOBALS['TL_CTE']['frontend_editing-bundle']['frontend_editing'] = FrontendEditing::class;

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['frontendediting.inserttags.ignoretags', 'replace'];

ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 1, [
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

$GLOBALS['DOWNLOADABLE-FILES'] = [];
$GLOBALS['BE_MOD']['accounts']['member']['tables'][] = 'tl_entity';

$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE']['FRONTEND-EDITING'] = [
    'feState' => [
        'recipients' => ['admin_email', 'form_*', 'member_*'],
        'email_replyTo' => ['admin_email', 'form_*', 'member_*'],
        'email_sender_name' => ['admin_email', 'form_*', 'member_*'],
        'email_recipient_cc' => ['admin_email', 'form_*', 'member_*'],
        'email_recipient_bcc' => ['admin_email', 'form_*', 'member_*'],
        'email_sender_address' => ['admin_email', 'form_*', 'member_*'],
        'email_subject' => ['admin_email', 'form_*', 'member_*'],
        'attachment_tokens' => ['admin_email', 'form_*', 'member_*'],
        'file_name' => ['admin_email', 'form_*', 'member_*'],
        'file_content' => ['admin_email', 'form_*', 'member_*'],
        'email_text' => ['admin_email', 'form_*', 'member_*'],
        'email_html' => ['admin_email', 'form_*', 'member_*']
    ],
    'feChange' => [
        'recipients' => ['admin_email', 'form_*', 'member_*'],
        'email_replyTo' => ['admin_email', 'form_*', 'member_*'],
        'email_sender_name' => ['admin_email', 'form_*', 'member_*'],
        'email_recipient_cc' => ['admin_email', 'form_*', 'member_*'],
        'email_recipient_bcc' => ['admin_email', 'form_*', 'member_*'],
        'email_sender_address' => ['admin_email', 'form_*', 'member_*'],
        'email_subject' => ['admin_email', 'form_*', 'member_*'],
        'attachment_tokens' => ['admin_email', 'form_*', 'member_*'],
        'file_name' => ['admin_email', 'form_*', 'member_*'],
        'file_content' => ['admin_email', 'form_*', 'member_*'],
        'email_text' => ['admin_email', 'form_*', 'member_*'],
        'email_html' => ['admin_email', 'form_*', 'member_*']
    ]
];