<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\System;
use Contao\StringUtil;
use Contao\FilesModel;
use Contao\Environment;
use Contao\Database;
use Contao\FormModel;
use Contao\FrontendUser;
use Contao\BackendUser;
use Contao\MemberModel;
use Contao\FormFieldModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export extends System
{

    public function __construct()
    {

        parent::__construct();
    }

    public function parseValue($strValue, $objField)
    {

        switch ($objField->type) {
            case 'dropzone':
                $arrFiles = [];
                $varValue = StringUtil::deserialize($strValue);
                if (is_array($varValue)) {
                    foreach ($varValue as $strUuid) {
                        if ($objFile = FilesModel::findByUuid($strUuid)) {
                            $arrFiles[] = Environment::get('url') . '/' . $objFile->path;
                        }
                    }
                }
                $strValue = implode(',', $arrFiles);
                break;
        }

        return $strValue;
    }

    public function download($strEntityGroupId, $strPath = '', $arrOnly = [])
    {

        $objEntityGroup = Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE id=?')->limit(1)->execute($strEntityGroupId);

        if (!$objEntityGroup->numRows) {
            return null;
        }

        $objForm = FormModel::findByPk($objEntityGroup->form);

        if (!$objForm) {
            return null;
        }

        if (TL_MODE == 'FE') {
            $strUser = FrontendUser::getInstance()->username;
        } else {
            $strUser = BackendUser::getInstance()->email;
        }

        $objSpreadsheet = new Spreadsheet();
        $objSpreadsheet->getProperties()
            ->setTitle($objForm->title)
            ->setCreator('Contao CMS')
            ->setLastModifiedBy($strUser);

        $objSheet = $objSpreadsheet->getActiveSheet();

        $arrRows = [];
        $objEntities = Database::getInstance()->prepare('SELECT * FROM tl_entity WHERE pid=? ORDER BY sorting')->execute($objEntityGroup->id);

        while ($objEntities->next()) {

            if (!empty($arrOnly) && is_array($arrOnly)) {
                if (!in_array($objEntities->id, $arrOnly)) {
                    continue;
                }
            }

            $arrEntity = [];
            $objValues = Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE pid=?')->execute($objEntities->id);
            if ($objMember = MemberModel::findByPk($objEntities->member)) {
                $arrEntity['Mitglied'] = $objMember->email;
            }

            while ($objValues->next()) {
                $objFormField = FormFieldModel::findByPk($objValues->field);
                if (!$objFormField) {
                    continue;
                }
                $arrEntity[($objFormField->label ?: $objFormField->name)] = $this->parseValue($objValues->varValue, $objFormField);
            }

            $objState = Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($objEntities->status);
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
            $objSheet->setCellValueByColumnAndRow($numCols + 1, 1, $strField);
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

        if (!$strPath) {

            header('Content-Disposition: attachment;filename="export-' . $objForm->alias . '.xlsx"');
            header('Cache-Control: max-age=0');
            header('Content-Type: application/vnd.ms-excel');

            $objXls = new Xlsx($objSpreadsheet);
            $objXls->save('php://output');
            exit;
        } else {

            $objXls = new Xlsx($objSpreadsheet);
            $objXls->save($strPath);
        }
    }
}