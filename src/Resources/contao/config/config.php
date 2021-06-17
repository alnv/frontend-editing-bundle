<?php

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
    ],
]);