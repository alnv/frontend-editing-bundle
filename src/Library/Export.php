<?php

namespace Alnv\FrontendEditingBundle\Library;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export extends \Controller {

    public function parseValue($strValue, $objField) {

        switch ($objField->type) {
            case 'dropzone':
                $arrFiles = [];
                $varValue = \StringUtil::deserialize($strValue);
                if (is_array($varValue)) {
                    foreach ($varValue as $strUuid) {
                        if ($objFile = \FilesModel::findByUuid($strUuid)) {
                            $arrFiles[] = \Environment::get('url') . '/' . $objFile->path;
                        }
                    }
                }
                $strValue = implode(',', $arrFiles);
                break;
        }

        return $strValue;
    }

    public function download() {

        $objEntityGroup = \Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE id=?')->limit(1)->execute(\Input::get('id'));

        if (!$objEntityGroup->numRows) {
            return null;
        }

        $objForm = \FormModel::findByPk($objEntityGroup->form);

        if (!$objForm) {
            return null;
        }

        $objSpreadsheet = new Spreadsheet();
        $objSpreadsheet->getProperties()
            ->setTitle($objForm->title)
            ->setCreator('Contao CMS')
            ->setLastModifiedBy(\BackendUser::getInstance()->email);

        $objSheet = $objSpreadsheet->getActiveSheet();

        $arrRows = [];
        $objEntities = \Database::getInstance()->prepare('SELECT * FROM tl_entity WHERE pid=? ORDER BY sorting')->execute($objEntityGroup->id);

        while ($objEntities->next()) {

            $arrEntity = [];
            $objValues = \Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE pid=?')->execute($objEntities->id);
            if ($objMember = \MemberModel::findByPk($objEntities->member)) {
                $arrEntity['Mitglied'] = $objMember->email;
            }

            while ($objValues->next()) {
                $objFormField = \FormFieldModel::findByPk($objValues->field);
                if (!$objFormField) {
                    continue;
                }
                $arrEntity[($objFormField->label?:$objFormField->name)] = $this->parseValue($objValues->varValue, $objFormField);
            }

            $objState = \Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($objEntities->status);
            $arrEntity['Status'] = $objState->name ?: '-';

            $arrRows[] = $arrEntity;
        }

        if (isset($GLOBALS['TL_HOOKS']['feEditingParseExport']) && is_array($GLOBALS['TL_HOOKS']['feEditingParseExport'])) {
            foreach ($GLOBALS['TL_HOOKS']['feEditingParseExport'] as $arrCallback) {
                $this->import($arrCallback[0]);
                $arrRows = $this->{$arrCallback[0]}->{$arrCallback[1]}($arrRows, $objEntities->reset(), $objEntityGroup, $objForm, $this);
            }
        }

        if (empty($arrRows)) {
            return null;
        }

        $arrFields = array_keys($arrRows[0]);

        foreach ($arrFields as $numCols => $strField) {
            $objSheet->setCellValueByColumnAndRow($numCols+1, 1, $strField);
        }

        $numRows = 2;
        foreach ($arrRows as $arrSet) {
            $numCols = 1;
            foreach ($arrSet as $strField => $strValue) {
                $objSheet->setCellValueByColumnAndRow($numCols, $numRows, $strValue);
                $numCols++;
            }
            $numRows++;
        }

        header('Content-Disposition: attachment;filename="export-' . $objForm->alias . '".xlsx');
        header('Cache-Control: max-age=0');
        header('Content-Type: application/vnd.ms-excel');

        $objXls = new Xlsx($objSpreadsheet);
        $objXls->save('php://output');
        exit;
    }
}