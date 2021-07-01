<?php

$GLOBALS['TL_DCA']['tl_entity_value'] = [
    'config' => [
        'closed' => true,
        'dataContainer' => 'Table',
        'ptable' => 'tl_entity',
        'onload_callback' => [['Alnv\FrontendEditingBundle\DataContainer\EntityValue', 'getVarValueWidget']],
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
            'fields' => ['field'],
            'panelLayout' => 'filter;search,limit'
        ],
        'label' => [
            'fields' => ['field', 'varValue'],
            'label_callback' => function ($arrRow) {
                $arrFields = ['field', 'varValue'];
                $arrReturn = [];
                foreach ($arrFields as $strField) {
                    $strValue = $arrRow[$strField];
                    switch ($strField) {
                        case 'field':
                            $objField = \FormFieldModel::findByPk($strValue);
                            if ($objField) {
                                $strValue = $objField->label ?: $objField->name;
                            }
                            break;
                        case 'varValue':
                            $strValue = \StringUtil::deserialize($strValue);
                            if (is_array($strValue)) {
                                $strValue = json_encode($strValue);
                                $strValue = str_replace('"', '', $strValue);
                                $strValue = str_replace('[', '', $strValue);
                                $strValue = str_replace(']', '', $strValue);
                                $strValue = str_replace('{', '', $strValue);
                                $strValue = str_replace('}', '', $strValue);
                                $strValue = str_replace('"', '', $strValue);
                                $strValue = str_replace(',', ' | ', $strValue);
                                $strValue = str_replace(':', ': ', $strValue);
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
            ]
        ]
    ],
    'palettes' => [
        'default' => 'varValue'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type'=>'integer','autoincrement' => true,'notnull' => true,'unsigned' => true]
        ],
        'tstamp' => [
            'flag' => 6,
            'sql' => ['type'=>'integer','notnull' => false,'unsigned' => true,'default' => 0]
        ],
        'pid' => [
            'sql' => ['type' => 'integer','notnull' => false,'unsigned' => true,'default' => 0]
        ],
        'field' => [
            'relation' => [
                'type' => 'hasOne',
                'load' => 'eager'
            ],
            'filter' => true,
            'foreignKey' => 'tl_form_field.CONCAT(label, " - ", name)',
            'sql' => ['type' => 'integer','notnull' => false,'unsigned' => true,'default' => 0]
        ],
        'varValue' => [
            'search' => true,
            'sql' => 'blob NULL'
        ]
    ]
];