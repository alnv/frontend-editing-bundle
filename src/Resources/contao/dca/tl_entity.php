<?php

use Alnv\FrontendEditingBundle\Library\Export;
use Alnv\FrontendEditingBundle\Library\Helpers;
use Alnv\FrontendEditingBundle\Library\States;
use Alnv\FrontendEditingBundle\Library\Tablelist;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\StringUtil;

$GLOBALS['TL_DCA']['tl_entity'] = [
    'config' => [
        'closed' => true,
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_entity_group',
        'ctable' => ['tl_entity_value'],
        'onload_callback' => [function () {
            if (!Input::get('export')) {
                return null;
            }
            (new Export())->download(Input::get('id'));
            Controller::redirect(preg_replace('/&(amp;)?export=[^&]*/i', '', preg_replace('/&(amp;)?' . preg_quote(\Input::get('export'), '/') . '=[^&]*/i', '', \Environment::get('request'))));
        }],
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['sorting'],
            'headerFields' => ['form'],
            'panelLayout' => 'filter;search,limit',
            'child_record_callback' => function ($arrRow) {
                $strTemplate = '';
                foreach ((new Tablelist())->getValues($arrRow['id']) as $arrField) {
                    $strValue = StringUtil::deserialize($arrField['value']);
                    if (is_array($strValue)) {
                        $strValue = Helpers::makeArrayReadable($strValue);
                    }
                    $strTemplate .= ($arrField['label'] ?: $arrField['name']) . ': ' . $strValue . '</br>';
                }
                return $strTemplate;
            }
        ],
        'operations' => [
            'edit' => [
                'icon' => 'header.svg',
                'href' => 'act=edit'
            ],
            'values' => [
                'icon' => 'edit.svg',
                'href' => 'table=tl_entity_value'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ]
        ],
        'global_operations' => [
            'export' => [
                'href' => 'export=1',
                'icon' => 'pickfile.svg',
                'label' => ['Exportieren', ''],
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ]
        ]
    ],
    'palettes' => [
        'default' => 'status,member,uploads'
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'autoincrement' => true, 'notnull' => true, 'unsigned' => true]
        ],
        'alias' => [
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'tstamp' => [
            'flag' => 6,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'created_at' => [
            'flag' => 6,
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'pid' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'sorting' => [
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true, 'default' => 0]
        ],
        'member' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 255,
                'tl_class' => 'w50',
                'decodeEntities' => true,
                'includeBlankOption' => true
            ],
            'relation' => [
                'type' => 'hasOne',
                'load' => 'lazy'
            ],
            'foreignKey' => 'tl_member.username',
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true]
        ],
        'status' => [
            'inputType' => 'select',
            'eval' => [
                'chosen' => true,
                'maxlength' => 255,
                'tl_class' => 'w50',
                'decodeEntities' => true,
                'includeBlankOption' => true
            ],
            'relation' => [
                'type' => 'hasOne',
                'load' => 'lazy'
            ],
            'save_callback' => [
                function ($strStatusId, DataContainer $dataContainer) {
                    if ($strStatusId && $dataContainer->activeRecord->status !== $strStatusId) {
                        (new States())->changeState($dataContainer->activeRecord->id, $strStatusId);
                    }
                    return $strStatusId;
                }
            ],
            'foreignKey' => 'tl_states.name',
            'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true]
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