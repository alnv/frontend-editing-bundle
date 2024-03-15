<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\ArrayUtil;
use Contao\Database;
use Contao\Date;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\FrontendUser;
use Contao\Input;
use Contao\StringUtil;


class Tablelist
{

    public function getEntities($arrSettings): array
    {

        $arrReturn = [];
        $arrQueryValues = [];
        $arrQueryColumns = [];

        if ($arrSettings['saveMemberId']) {
            $arrQueryColumns[] = 'member=?';
            $arrQueryValues[] = FrontendUser::getInstance()->id;
        }

        $objEntities = Database::getInstance()->prepare('SELECT * FROM tl_entity' . (empty($arrQueryColumns) ? '' : ' WHERE ' . implode(' AND ', $arrQueryColumns)))->execute($arrQueryValues);

        if (!$objEntities->numRows) {
            return $arrReturn;
        }

        while ($objEntities->next()) {

            $arrSet = $objEntities->row();
            $arrValues = $this->getValues($objEntities->id);

            if (empty($arrValues)) {
                continue;
            }

            $arrSet['status'] = (new DataContainer())->getStatus($arrSet['status']);
            $arrSet['title'] = $arrSettings['titleColumn'] ? StringUtil::parseSimpleTokens($arrSettings['titleColumn'], $this->getFormTokens($arrValues)) : '';
            $arrSet['values'] = $arrValues;
            $arrSet['buttons'] = $this->getButtons($arrSet);
            $arrSet['updated_at'] = (new Date($arrSet['stamp']))->datim;
            $arrSet['created_at'] = (new Date($arrSet['created_at']))->datim;
            $arrSet['group'] = Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE id=?')->limit(1)->execute($objEntities->pid)->row();

            if ($objForm = FormModel::findByPk($arrSet['group']['form'])) {
                $arrSet['group']['form'] = $objForm->row();
                if (!in_array($arrSet['group']['form']['id'], $arrSettings['forms'])) {
                    continue;
                }
            }

            $arrSet['uploads'] = FileHelper::getFiles($arrSet['uploads'], StringUtil::deserialize($arrSet['uploadsOrderSRC'], true));
            if (!empty($arrSet['status']['uploads'])) {
                ArrayUtil::arrayInsert($arrSet['uploads'], 0, $arrSet['status']['uploads']);
            }

            $arrReturn[] = $arrSet;
        }

        return $arrReturn;
    }

    protected function getFormTokens($arrValues): array
    {

        $arrReturn = [];

        foreach ($arrValues as $strField => $arrField) {
            $arrReturn['form_' . $strField] = $arrField['value'];
        }

        return $arrReturn;
    }

    protected function getAlias(): string
    {

        return md5(time() . uniqid());
    }

    public function getCreateButton($strAlias = ''): array
    {

        global $objPage;

        if (!$strAlias) {
            $strAlias = $this->getAlias();
        }

        return [
            'icon' => 'system/themes/flexible/icons/new.svg',
            'label' => $GLOBALS['TL_LANG']['MSC']['createButton'],
            'href' => $objPage->getFrontendUrl('/' . $strAlias) . '?form=' . Input::get('form')
        ];
    }

    protected function getButtons($arrEntity): array
    {

        global $objPage;

        $arrReturn = [];

        $arrReturn['edit'] = [
            'href' => $objPage->getFrontendUrl('/' . $arrEntity['alias']),
            'icon' => 'system/themes/flexible/icons/edit.svg',
            'label' => $GLOBALS['TL_LANG']['MSC']['editButton']
        ];

        $arrReturn['copy'] = [
            'href' => $objPage->getFrontendUrl('/' . $this->getAlias()) . '?copy=' . $arrEntity['id'] . '&form=' . $this->getFormIdByEntityPid($arrEntity['pid']),
            'icon' => 'system/themes/flexible/icons/copy.svg',
            'label' => $GLOBALS['TL_LANG']['MSC']['copyButton']
        ];

        $arrReturn['delete'] = [
            'href' => $objPage->getFrontendUrl('/delete') . '?id=' . $arrEntity['id'],
            'icon' => 'system/themes/flexible/icons/delete.svg',
            'label' => $GLOBALS['TL_LANG']['MSC']['deleteButton'],
            'attributes' => ' onclick="return confirm(\'Sind Sie sicher, dass Sie den Beitrag löschen wollen?\')"'
        ];

        $arrExcludes = (new States())->getStateExcludes($arrEntity['status']['id']);
        if (!empty($arrExcludes)) {
            foreach ($arrExcludes as $strExclude) {
                unset($arrReturn[$strExclude]);
            }
        }

        return $arrReturn;
    }

    protected function getFormIdByEntityPid($strPid)
    {

        $objForm = Database::getInstance()->prepare('SELECT * FROM tl_form WHERE id=(SELECT form FROM tl_entity_group WHERE id=? LIMIT 1)')->limit(1)->execute($strPid);

        return $objForm->id ?: '';
    }

    public function getValues($strEntityId): array
    {

        $arrValues = [];
        $objValues = Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE pid=?')->execute($strEntityId);
        while ($objValues->next()) {

            $objField = FormFieldModel::findByPk($objValues->field);
            if (!$objField) {
                continue;
            }

            $arrSet = $objField->row();
            $arrSet['value'] = $objValues->varValue;
            $arrValues[$objField->name] = $arrSet;
        }

        return $arrValues;
    }
}