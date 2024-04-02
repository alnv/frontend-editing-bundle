<?php

namespace Alnv\FrontendEditingBundle\Elements;

use Alnv\FrontendEditingBundle\Library\States;
use Alnv\FrontendEditingBundle\Library\DataContainer;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\Input;
use Contao\StringUtil;
use Contao\FormModel;
use Contao\FrontendUser;
use Contao\FrontendTemplate;
use Contao\ContentElement;
use Contao\Environment;
use Alnv\FrontendEditingBundle\Library\Form;
use Alnv\FrontendEditingBundle\Library\Tablelist;
use Alnv\FrontendEditingBundle\Library\FileHelper;
use Contao\BackendTemplate;

class FrontendEditing extends ContentElement
{

    protected array $arrSettings = [
        'saveMemberId' => false,
        'showForm' => true,
        'submitButtons' => []
    ];

    protected array $arrForms = [];
    protected $strAlias = null;
    protected array $arrSubmitted = [];
    protected $strSubmitType = null;
    protected $strActiveForm = null;

    protected $strTemplate = 'ce_frontend_editing';

    public function generate(): string
    {

        if (TL_MODE == 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### FRONTEND EDITING ###';
            $objTemplate->id = $this->id;
            return $objTemplate->parse();
        }

        $this->setSettings();

        if (!$this->getMemberId() && $this->arrSettings['saveMemberId']) {
            throw new AccessDeniedException('Page access denied: ' . Environment::get('uri'));
        }

        $this->setAlias();
        $this->setForms();
        $this->deleteAndReload();
        $this->strActiveForm = $this->getFormId();

        if ($_GET['auto_item']) {
            if (!in_array($this->strActiveForm, array_keys($this->arrForms))) {
                return '';
            }
        }

        return parent::generate();
    }

    protected function setAlias(): void
    {

        if ($_GET['auto_item']) {
            $this->strAlias = Input::get('auto_item');
        } else {
            $this->strAlias = md5(time() . uniqid());
        }
    }

    protected function deleteAndReload()
    {

        if ($_GET['auto_item'] !== 'delete') {
            return null;
        }

        $arrValues = [Input::get('id')];

        global $objPage;

        if ($this->arrSettings['saveMemberId']) {
            $arrValues[] = $this->getMemberId();
        }

        $objEntity = Database::getInstance()->prepare('SELECT * FROM tl_entity WHERE id=?' . ($this->arrSettings['saveMemberId'] ? ' AND member=?' : ''))->limit(1)->execute($arrValues);
        if (!$objEntity->numRows) {
            return null;
        }

        if (!(new States())->isAllowed('delete', $objEntity->status)) {
            throw new AccessDeniedException('Page access denied: ' . \Environment::get('uri'));
        }

        if (isset($GLOBALS['TL_HOOKS']['onDeleteEntity']) && is_array($GLOBALS['TL_HOOKS']['onDeleteEntity'])) {
            foreach ($GLOBALS['TL_HOOKS']['onDeleteEntity'] as $arrCallback) {
                if (is_array($arrCallback)) {
                    $this->import($arrCallback[0]);
                    $this->{$arrCallback[0]}->{$arrCallback[1]}($objEntity->row(), $this);
                } elseif (\is_callable($arrCallback)) {
                    $arrCallback($objEntity->row(), $this);
                }
            }
        }

        $objValues = Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE pid=?')->execute($objEntity->id);

        while ($objValues->next()) {
            Database::getInstance()->prepare('DELETE FROM tl_entity_value WHERE id=?')->limit(1)->execute($objValues->id);
        }

        Database::getInstance()->prepare('DELETE FROM tl_entity WHERE id=?')->limit(1)->execute($objEntity->id);
        Controller::redirect($objPage->getFrontendUrl());
    }

    protected function compile()
    {

        if ($this->arrSettings['showForm']) {
            $this->generateForm();
        } else {
            $this->generateList();
        }
    }

    protected function setForms()
    {

        $arrForms = [];
        $arrFormsId = StringUtil::deserialize($this->forms, true);
        foreach ($arrFormsId as $strFormId) {
            $objForm = FormModel::findByPk($strFormId);
            if (!$objForm) {
                continue;
            }
            $arrForms[$objForm->id] = $objForm->row();
        }

        $this->arrForms = $arrForms;
    }

    protected function getFormId()
    {

        if ($_GET['auto_item']) {
            $arrEntity = (new Form())->getEntityByAlias($this->strAlias);
            if (!empty($arrEntity)) {
                $objGroup = Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE id=?')->limit(1)->execute($arrEntity['pid']);
                return $objGroup->form;
            }
        }

        $strFormId = Input::get('form');
        if ($strFormId && in_array($strFormId, array_keys($this->arrForms))) {
            return $strFormId;
        }

        if (!empty($this->arrForms) && count($this->arrForms) < 2) {
            $arrForms = array_keys($this->arrForms);
            return $arrForms[0] ?: null;
        }

        return null;
    }

    protected function generateList()
    {

        $strTemplate = 'fre_tablelist';
        $objTemplate = new FrontendTemplate($strTemplate);

        FileHelper::sendFileToBrowser();

        $objTemplate->setData([
            'id' => $this->id,
            'forms' => $this->arrForms,
            'activeForm' => Input::get('form') ?: '',
            'titleHeadline' => $this->arrSettings['titleHeadlineColumn'],
            'create' => (new Tablelist())->getCreateButton($this->strAlias),
            'entities' => (new Tablelist())->getEntities($this->arrSettings)
        ]);

        $this->Template->listTpl = $objTemplate->parse();
    }

    protected function generateForm()
    {

        $strTemplate = 'fre_form';
        $blnSubmit = $this->isSubmitted();
        $arrEntity = (new Form())->getEntityByAlias($this->strAlias);
        $arrFields = (new Form())->getFormFieldsByFormId($this->strActiveForm, $this->strAlias);

        if (!(new States())->isAllowed('edit', ($arrEntity['status'] ?? ''))) {
            $this->redirectBack();
        }

        $arrTemplateData = [
            'formId' => $this->strActiveForm,
            'submitId' => $this->getSubmitId(),
            'buttons' => $this->arrSettings['submitButtons'],
            'entity' => $arrEntity ?: [],
            'fields' => []
        ];
        $objTemplate = new FrontendTemplate($strTemplate);

        foreach ($arrFields as $strField => $objWidget) {
            if ($blnSubmit) {
                $objWidget->validate();
                if ($objWidget->hasErrors()) {
                    $blnSubmit = false;
                }
                if (!is_numeric($strField)) {
                    $this->arrSubmitted[$strField] = $objWidget->value;
                }
            }
            $arrTemplateData['fields'][$strField] = $objWidget->parse();
        }

        switch ($this->strSubmitType) {
            case 'save':
                if ($blnSubmit) {
                    $strCurrentAlias = $this->save();
                    $this->redirectTo($strCurrentAlias);
                }
                break;
            case 'back':
                $this->redirectBack();
                break;
            case 'changeNsave':
            case 'saveNback':
                if ($blnSubmit) {
                    $this->save();
                    $this->redirectBack();
                }
                break;
            case 'saveNcreate':
                if ($blnSubmit) {
                    $this->save();
                }
                break;
        }

        $objTemplate->setData($arrTemplateData);
        $this->Template->formTpl = $objTemplate->parse();
    }

    protected function save()
    {

        $arrEntity = (new Form())->createEntityByAliasAndFormId($this->strAlias, $this->strActiveForm, $this->getMemberId());
        $arrFields = (new Form())->getRawFormFieldsByFormId($this->strActiveForm);

        foreach ($this->arrSubmitted as $strName => $varValue) {
            $objField = $arrFields[$strName];
            (new Form())->setValue($varValue, $objField, $arrEntity['id']);
        }

        if (!$arrEntity['status']) {
            (new Form())->setStatus($this->arrSettings['status'], $arrEntity['id']);
        }

        if ($arrEntity['status'] && $this->arrSettings['status'] != $this->startStatus) {
            (new Form())->setStatus($this->arrSettings['status'], $arrEntity['id']);
        }

        if (isset($GLOBALS['TL_HOOKS']['onSaveEntity']) && is_array($GLOBALS['TL_HOOKS']['onSaveEntity'])) {
            foreach ($GLOBALS['TL_HOOKS']['onSaveEntity'] as $arrCallback) {
                if (is_array($arrCallback)) {
                    $this->import($arrCallback[0]);
                    $this->{$arrCallback[0]}->{$arrCallback[1]}($this->arrSubmitted, $arrEntity['id'], $this);
                } elseif (\is_callable($arrCallback)) {
                    $arrCallback($this->arrSubmitted, $arrEntity['id'], $this);
                }
            }
        }

        return $arrEntity['alias'];
    }

    protected function redirectTo($strAlias = ''): void
    {

        global $objPage;

        Controller::redirect($objPage->getFrontendUrl(($strAlias ? '/' . $strAlias : '')));
    }

    protected function redirectBack(): void
    {

        global $objPage;

        Controller::redirect($objPage->getFrontendUrl());
    }

    protected function isSubmitted(): bool
    {

        $strSubmitId = Input::post('FORM_SUBMIT');
        $this->strSubmitType = null;

        if (!$strSubmitId) {
            return false;
        }

        if (Input::post('changeNsave') !== null) {
            $this->strSubmitType = 'changeNsave';
            $this->arrSettings['status'] = Input::post('changeNsave');
        }

        foreach ($this->arrSettings['submitButtons'] as $strId => $strLabel) {
            if (Input::post($strId) === $strSubmitId) {
                $this->strSubmitType = $strId;
            }
        }

        if (!$this->strSubmitType) {
            return false;
        }

        return true;
    }

    protected function getSubmitId(): string
    {
        return 'form-' . $this->id;
    }

    protected function setSettings(): void
    {

        $this->arrSettings['saveMemberId'] = $this->addMemberPermissions && $this->addMemberId;
        $this->arrSettings['showForm'] = $_GET['auto_item'] || $this->disableList;
        $this->arrSettings['submitButtons'] = (new DataContainer())->getSubmitByChoice($this->submitButtons);
        $this->arrSettings['status'] = $this->startStatus ?: 0;
        $this->arrSettings['titleColumn'] = $this->titleColumn ?: '';
        $this->arrSettings['forms'] = StringUtil::deserialize($this->forms, true);
        $this->arrSettings['titleHeadlineColumn'] = $this->titleHeadlineColumn ?: '';
    }

    protected function getMemberId()
    {

        if (!$this->arrSettings['saveMemberId']) {
            return null;
        }

        if (!FE_USER_LOGGED_IN) {
            return 0;
        }

        return FrontendUser::getInstance()->id;
    }
}