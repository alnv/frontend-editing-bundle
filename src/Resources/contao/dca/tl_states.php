<?php

use Contao\DC_Table;
# use NotificationCenter\Model\Notification;

$GLOBALS['TL_DCA']['tl_states'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
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
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm']??'') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm']??'') . '\'))return false;Backend.getScrollOffset()"'
            ]
        ]
    ],
    'palettes' => [
        'default' => 'name,note,notification,color,excludes,uploads'
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
        'notification' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'tl_class' => 'w50',
                'mandatory' => false,
                'includeBlankOption' => true
            ],
            'options_callback' => function () {
                $arrReturn = [];
                /*
                $objNotificationCollection = \NotificationCenter\Model\Notification::findByType('feState');
                if (null !== $objNotificationCollection) {
                    while ($objNotificationCollection->next()) {
                        $arrReturn[$objNotificationCollection->id] = $objNotificationCollection->title;
                    }
                }
                */
                return $arrReturn;
            },
            'sql' => ['type' => 'integer','notnull' => false,'unsigned' => true,'default' => 0]
        ],
        'color' => [
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'colorpicker' => true,
                'tl_class' => 'w50 wizard'
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => '']
        ],
        'excludes' => [
            'inputType' => 'checkbox',
            'eval' => [
                'multiple' => true,
                'tl_class' => 'w50 clr'
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_states'],
            'options_callback' => function () {
                return ['delete', 'edit'];
            },
            'sql' => 'blob NULL'
        ],
        'note' => [
            'inputType' => 'textarea',
            'eval' => [
                'rte' => 'tinyMCE',
                'tl_class' => 'clr',
                'helpwizard' => true
            ],
            'explanation' => 'insertTags',
            'sql' => "mediumtext NULL"
        ],
        'uploads' => [
            'inputType' => 'fileTree',
            'eval' => [
                'multiple' => true,
                'tl_class' => 'clr',
                'fieldType' => 'checkbox',
                'orderField' => 'uploadsOrderSRC',
                'isDownloads' => true,
                'files' => true
            ],
            'sql' => "blob NULL"
        ],
        'uploadsOrderSRC' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['uploadsOrderSRC'],
            'sql' => 'blob NULL'
        ]
    ]
];