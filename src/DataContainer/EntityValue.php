<?php

namespace Alnv\FrontendEditingBundle\DataContainer;

class EntityValue {

    public function getVarValueWidget(\DataContainer $dc) {

        $objEntityValue = \Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE id=?')->limit(1)->execute(\Input::get('id'));
        $objField = \FormFieldModel::findByPk($objEntityValue->field);

        if (!$objField) {
            return null;
        }

        $strClass = $GLOBALS['BE_FFL'][$objField->type];
        if (!class_exists($strClass)) {
            $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['inputType'] = 'multiColumnWizard';
            $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['eval']['columnFields'] = [];
            $varValues = \StringUtil::deserialize($objEntityValue->varValue, true);
            if (empty($varValues)) {
                return null;
            }
            $arrFields = array_keys($varValues[0]);
            foreach ($arrFields as $strField) {
                $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['eval']['columnFields'][$strField] = [
                    'inputType' => 'text'
                ];
            }
            return null;
        }

        $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['inputType'] = $objField->type;
        $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['eval']['tl_class'] = 'w50';

        if (in_array( $objField->type, ['select', 'checkbox', 'radio'])) {
            $arrOptions = [];
            $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['eval']['includeBlankOption'] = true;
            foreach (\StringUtil::deserialize($objField->options, true) as $arrOption) {
                $arrOptions[$arrOption['value']] = $arrOption['label'];
            }
            $GLOBALS['TL_DCA']['tl_entity_value']['fields']['varValue']['options'] = $arrOptions;
        }
    }
}