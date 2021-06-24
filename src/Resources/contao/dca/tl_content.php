<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'addMemberPermissions';
$GLOBALS['TL_DCA']['tl_content']['palettes']['frontend_editing'] = '{type_legend},type,headline;{include_legend},forms;{editing_legend},addMemberPermissions,disableList,submitButtons,startStatus,titleHeadlineColumn,titleColumn;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['addMemberPermissions']  = 'addMemberId';

$GLOBALS['TL_DCA']['tl_content']['fields']['forms'] = [
    'inputType' => 'checkboxWizard',
    'eval' => [
        'mandatory' => true,
        'tl_class' => 'clr',
        'multiple' => true
    ],
    'options_callback' => ['tl_content', 'getForms'],
    'sql' => "blob NULL"
];
$GLOBALS['TL_DCA']['tl_content']['fields']['addMemberPermissions'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
        'multiple' => false,
        'submitOnChange' => true
    ],
    'sql' => ['type'=>'string', 'fixed'=>true, 'length'=>1, 'default'=>'']
];
$GLOBALS['TL_DCA']['tl_content']['fields']['addMemberId'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
        'multiple' => false
    ],
    'sql' => ['type'=>'string', 'fixed'=>true, 'length'=>1, 'default'=>'']
];
$GLOBALS['TL_DCA']['tl_content']['fields']['disableList'] = [
    'inputType' => 'checkbox',
    'eval' => [
        'tl_class' => 'clr',
        'multiple' => false
    ],
    'sql' => ['type'=>'string', 'fixed'=>true, 'length'=>1, 'default'=>'']
];
$GLOBALS['TL_DCA']['tl_content']['fields']['submitButtons'] = [
    'inputType' => 'checkboxWizard',
    'eval' => [
        'tl_class' => 'clr',
        'multiple' => true
    ],
    'options_callback' => ['Alnv\FrontendEditingBundle\Library\DataContainer', 'getSubmits'],
    'sql' => 'blob NULL'
];
$GLOBALS['TL_DCA']['tl_content']['fields']['titleHeadlineColumn'] = [
    'inputType' => 'text',
    'eval' => [
        'maxlength' => 255,
        'tl_class' => 'w50',
        'decodeEntities' => true
    ],
    'sql' => ['type'=>'string', 'length'=>255, 'default'=>'']
];
$GLOBALS['TL_DCA']['tl_content']['fields']['titleColumn'] = [
    'inputType' => 'textarea',
    'eval' => [
        'rte' => 'ace',
        'maxlength' => 255,
        'allowHtml' => true,
        'tl_class' => 'long clr'
    ],
    'sql' => ['type'=>'string', 'length'=>255, 'default'=>'']
];
$GLOBALS['TL_DCA']['tl_content']['fields']['startStatus'] = [
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
    'foreignKey' => 'tl_states.name',
    'sql' => ['type' => 'integer','notnull' => false,'unsigned' => true]
];
