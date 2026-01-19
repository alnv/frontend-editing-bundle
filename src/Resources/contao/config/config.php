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