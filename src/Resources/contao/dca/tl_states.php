<?php

$GLOBALS['TL_DCA']['tl_states'] = [
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'flag' => 12,
            'fields' => ['name'],
            'panelLayout' => 'filter;search,limit'
        ],
        'label' => [
            'fields' => ['name'],
            'showColumns' => true
        ],
        'operations' => [
            'edit' => [
                'icon' => 'header.svg',
                'href' => 'act=edit'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ]
        ]
    ],
    'palettes' => [
        'default' => 'name,color'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type'=>'integer', 'autoincrement'=>true, 'notnull'=>true, 'unsigned'=>true]
        ],
        'tstamp' => [
            'flag' => 6,
            'sql' => ['type' => 'integer','notnull' => false,'unsigned' => true,'default' => 0]
        ],
        'name' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 128,
                'tl_class' => 'w50',
                'mandatory' => true
            ],
            'search' => true,
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '']
        ],
        'color' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'colorpicker' => true,
                'tl_class' => 'w50 wizard'
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '']
        ]
    ]
];