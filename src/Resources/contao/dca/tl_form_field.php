<?php

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['submit'] .= ';{frontend_editing_legend},status';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['status'] = [
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
    'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true]
];