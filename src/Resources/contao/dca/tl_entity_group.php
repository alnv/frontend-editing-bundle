<?php

$GLOBALS['TL_DCA']['tl_entity_group'] = [
    'config' => [
        'dataContainer' => 'Table',
        'closed' => true,
        'notEditable' => true,
        'notCopyable' => true,
        'ctable' => ['tl_entity'],
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
            'fields' => ['form'],
            'panelLayout' => 'filter;search,limit'
        ],
        'label' => [
            'fields' => ['form'],
            'label_callback' => function ($arrRow) {
                $arrFields = ['form'];
                $arrReturn = [];
                foreach ($arrFields as $strField) {
                    $strValue = $arrRow[$strField];
                    switch ($strField) {
                        case 'form':
                            $objForm = \FormModel::findByPk($strValue);
                            if ($objForm) {
                                $strValue = $objForm->title;
                            }
                            break;
                    }
                    $arrReturn[$strField] = $strValue;
                }
                return $arrReturn;
            },
            'showColumns' => true
        ],
        'operations' => [
            'edit' => [
                'icon' => 'edit.svg',
                'href' => 'table=tl_entity'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ]
        ]
    ],
    'fields' => [
        'id' => [
            'sql' => ['type'=>'integer','autoincrement'=>true,'notnull'=>true,'unsigned'=>true]
        ],
        'tstamp' => [
            'flag' => 6,
            'sql' => ['type'=>'integer','notnull'=>false,'unsigned'=>true,'default' => 0]
        ],
        'form' => [
            'relation' => [
                'type' => 'hasOne',
                'load' => 'lazy'
            ],
            'foreignKey' => 'tl_form.title',
            'sql' => ['type'=>'integer','notnull'=>false,'unsigned'=>true,'default' => 0]
        ]
    ]
];