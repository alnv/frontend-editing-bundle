<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\ContentModel;
use Contao\System;
use Contao\Form AS ContaoForm;
use Contao\Controller;
use Contao\FormFieldModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\FormHidden;
use Contao\Database;

class Form extends System
{

    public function getRawFormFieldsByFormId($strFormId): array
    {

        Controller::loadDataContainer('tl_form_field');

        $arrFields = [];
        $objFields = FormFieldModel::findPublishedByPid($strFormId);

        if ($objFields !== null) {
            while ($objFields->next()) {
                if ($objFields->name && isset($GLOBALS['TL_DCA']['tl_form_field']['palettes'][$objFields->type]) && preg_match('/[,;]name[,;]/', $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$objFields->type])) {
                    $arrFields[$objFields->name] = $objFields->current();
                } else {
                    $arrFields[] = $objFields->current();
                }
            }
        }

        $objContentEntity = Database::getInstance()->prepare('SELECT * FROM tl_content WHERE type=?')->limit(1)->execute('frontend_editing');
        if ($objContentEntity->forms) {

            $objContentElement = ContentModel::findByPk($objContentEntity->id);
            $objForm = new ContaoForm($objContentElement);

            if (isset($GLOBALS['TL_HOOKS']['compileFormFields']) && is_array($GLOBALS['TL_HOOKS']['compileFormFields'])) {
                foreach ($GLOBALS['TL_HOOKS']['compileFormFields'] as $arrCallback) {
                    System::importStatic($arrCallback[0])->{$arrCallback[1]}($arrFields, $strFormId, $objForm);
                }
            }

        }

        return $arrFields;
    }

    public function getFormFieldsByFormId($strFormId, $strAlias = ''): array
    {

        $arrReturn = [];
        $arrFields = $this->getRawFormFieldsByFormId($strFormId);
        $arrEntity = $this->getEntityByAlias($strAlias);

        if (!empty($arrFields) && is_array($arrFields)) {

            $intRow = 0;
            $intMaxRow = \count($arrFields);

            foreach ($arrFields as $strField => $objField) {

                $strClass = $GLOBALS['TL_FFL'][$objField->type] ?: null;

                if (!class_exists($strClass)) {
                    continue;
                }

                $arrData = $objField->row();
                $arrData['decodeEntities'] = true;
                $arrData['rowClass'] = 'row_' . $intRow . (($intRow == 0) ? ' row_first' : (($intRow == ($intMaxRow - 1)) ? ' row_last' : '')) . ((($intRow % 2) == 0) ? ' even' : ' odd');

                if ($objField->type == 'password') {
                    ++$intRow;
                    ++$intMaxRow;
                    $arrData['rowClassConfirm'] = 'row_' . $intRow . (($intRow == ($intMaxRow - 1)) ? ' row_last' : '') . ((($intRow % 2) == 0) ? ' even' : ' odd');
                }

                if (!empty($arrData['value']) && !in_array('value', StringUtil::trimsplit('[,;]', ($GLOBALS['TL_DCA']['tl_form_field']['palettes'][$objField->type] ?: '')))) {
                    $arrData['value'] = '';
                }

                if (Input::post($strField) !== null) {
                    $arrData['value'] = Input::post($strField);
                } else {
                    if ($arrEntity) {
                        $arrData['value'] = $this->getValue($objField->id, $arrEntity['id'], $objField);
                    } elseif (Input::get('copy')) {
                        $arrData['value'] = $this->getValue($objField->id, Input::get('copy'), $objField);
                    }
                }

                $arrData['value'] = $arrData['value'] ?? '';

                if ($arrData['value'] === '') {
                    $arrData['value'] = $arrData['default'] ?? '';
                }

                if ($arrData['type'] == 'submit') {
                    $arrData['name'] = 'changeNsave';
                    if ($arrData['status']) {
                        if (($arrEntity['status'] ?? '') == $arrData['status']) {
                            --$intMaxRow;
                            continue;
                        }
                        $arrData['value'] = $arrData['status'];
                    } else {
                        $arrData['value'] = '';
                    }
                }

                $objWidget = new $strClass($arrData);

                if ($objWidget instanceof FormHidden) {
                    --$intMaxRow;
                }

                $objWidget->required = (bool) $objField->mandatory;
                $arrReturn[$strField] = $objWidget;
            }
        }

        return $arrReturn;
    }

    public function getEntityByAlias($strAlias): array|null
    {

        $objEntity = Database::getInstance()->prepare('SELECT * FROM tl_entity WHERE alias=?')->limit(1)->execute($strAlias);
        if (!$objEntity->numRows) {
            return null;
        }

        return $objEntity->row();
    }

    protected function getValue($strFieldId, $strEntityId, $objField)
    {

        $objEntityValue = Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE field=? AND pid=?')->limit(1)->execute($strFieldId, $strEntityId);

        if (!$objEntityValue->numRows) {
            return null;
        }

        return $this->parseValue2Fe($objEntityValue->varValue, $objField);
    }

    public function setValue($strValue, $objField, $strEntityId): array
    {

        $arrSet = [
            'tstamp' => time(),
            'pid' => $strEntityId,
            'field' => $objField->id,
            'varValue' => $this->parseValue2Db($strValue, $objField),
        ];

        $objEntityValue = Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE field=? AND pid=?')->limit(1)->execute($objField->id, $strEntityId);
        if ($objEntityValue->numRows) {
            Database::getInstance()->prepare('UPDATE tl_entity_value %s WHERE id=?')->set($arrSet)->limit(1)->execute($objEntityValue->id);
        } else {
            Database::getInstance()->prepare('INSERT INTO tl_entity_value %s')->set($arrSet)->execute();
        }

        return $arrSet;
    }

    protected function parseValue2Db($strValue, $objField)
    {
        return $strValue;
    }

    protected function parseValue2Fe($strValue, $objField)
    {
        return $strValue;
    }

    public function createEntityByAliasAndFormId($strAlias, $strFormId, $strMember = null): array
    {

        $objEntityGroup = Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE form=?')->limit(1)->execute($strFormId);

        if (!$objEntityGroup->numRows) {
            $objInsert = Database::getInstance()->prepare('INSERT INTO tl_entity_group %s')->set([
                'form' => $strFormId,
                'tstamp' => time()
            ])->execute();
            $strGroupId = $objInsert->insertId;
        } else {
            $strGroupId = $objEntityGroup->id;
        }

        $objEntity = Database::getInstance()->prepare('SELECT * FROM tl_entity WHERE alias=? AND pid=?')->limit(1)->execute($strAlias, $strGroupId);

        if (!$objEntity->numRows) {

            $arrSet = [
                'tstamp' => time(),
                'pid' => $strGroupId,
                'created_at' => time(),
                'alias' => md5(time() . uniqid()),
                'member' => $strMember ?: null
            ];

            $objInsert = Database::getInstance()->prepare('INSERT INTO tl_entity %s')->set($arrSet)->execute();
            $arrSet['id'] = $objInsert->insertId;
            return $arrSet;

        } else {
            $arrSet = [
                'tstamp' => time()
            ];

            Database::getInstance()->prepare('UPDATE tl_entity %s WHERE id=?')->limit(1)->set($arrSet)->execute($objEntity->id);
        }

        return $objEntity->row();
    }

    public function setStatus($strStatusID, $strEntityId): void
    {
        (new States())->changeState($strEntityId, $strStatusID);
    }
}