<?php

namespace Alnv\FrontendEditingBundle\Elements;

class FrontendEditing extends  \ContentElement {

    protected $arrSettings = [
        'saveMemberId' => false,
        'showForm' => true,
        'submitButtons' => []
    ];

    protected $arrForms = [];
    protected $strAlias = null;
    protected $arrSubmitted = [];
    protected $strSubmitType = null;
    protected $strActiveForm = null;
    protected $strTemplate = 'ce_frontend_editing';

    public function generate() {

        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### FRONTEND EDITING ###';
            $objTemplate->id = $this->id;
            return $objTemplate->parse();
        }

        $this->setSettings();

        if (!$this->getMemberId() && $this->arrSettings['saveMemberId']) {
            throw new \CoreBundle\Exception\AccessDeniedException('Page access denied: ' . \Environment::get('uri'));
        }

        $this->setAlias();
        $this->setForms();
        $this->deleteAndReload();
        $this->strActiveForm = $this->getFormId();

        return parent::generate();
    }

    protected function setAlias() {

        if ($_GET['auto_item']) {
            $this->strAlias = \Input::get('auto_item');
        } else {
            $this->strAlias = md5(time().uniqid());
        }
    }

    protected function deleteAndReload() {

        if ($_GET['auto_item'] !== 'delete') {
            return null;
        }

        $arrValues = [\Input::get('id')];

        global $objPage;

        if ($this->arrSettings['saveMemberId']) {
            $arrValues[] = $this->getMemberId();
        }

        $objEntity = \Database::getInstance()->prepare('SELECT * FROM tl_entity WHERE id=?'.($this->arrSettings['saveMemberId'] ? ' AND member=?':''))->limit(1)->execute($arrValues);
        if (!$objEntity->numRows) {
            return null;
        }

        $objValues = \Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE pid=?')->execute($objEntity->id);
        while ($objValues->next()) {
            \Database::getInstance()->prepare('DELETE FROM tl_entity_value WHERE id=?')->limit(1)->execute($objValues->id);
        }

        \Database::getInstance()->prepare('DELETE FROM tl_entity WHERE id=?')->limit(1)->execute($objEntity->id);
        \Controller::redirect($objPage->getFrontendUrl());
    }

    protected function compile() {

        if ($this->arrSettings['showForm']) {
            $this->generateForm();
        } else {
            $this->generateList();
        }
    }

    protected function setForms() {

        $arrForms = [];
        $arrFormsId = \StringUtil::deserialize($this->forms, true);
        foreach ($arrFormsId as $strFormId) {
            $objForm = \FormModel::findByPk($strFormId);
            if (!$objForm) {
                continue;
            }
            $arrForms[$objForm->id] = $objForm->row();
        }

        $this->arrForms = $arrForms;
    }

    protected function getFormId() {

        if ($_GET['auto_item']) {
            $arrEntity = (new \Alnv\FrontendEditingBundle\Library\Form())->getEntityByAlias($this->strAlias);
            if (!empty($arrEntity)) {
                $objGroup = \Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE id=?')->limit(1)->execute($arrEntity['pid']);
                return $objGroup->form;
            }
        }

        $strFormId = \Input::get('form');
        if ($strFormId && in_array($strFormId, array_keys($this->arrForms))) {
            return $strFormId;
        }

        if (!empty($this->arrForms) && count($this->arrForms) < 2) {
            $arrForms = array_keys($this->arrForms);
            return $arrForms[0] ?: null;
        }

        return null;
    }

    protected function generateList() {

        $strTemplate = 'fre_tablelist';
        $objTemplate = new \FrontendTemplate($strTemplate);
        
        $objTemplate->setData([
            'id' => $this->id,
            'forms' => $this->arrForms,
            'activeForm' => \Input::get('form') ?: '',
            'titleHeadline' => $this->arrSettings['titleHeadlineColumn'],
            'create' => (new \Alnv\FrontendEditingBundle\Library\Tablelist())->getCreateButton($this->strAlias),
            'entities' => (new \Alnv\FrontendEditingBundle\Library\Tablelist())->getEntities($this->arrSettings)
        ]);
        $this->Template->listTpl = $objTemplate->parse();
    }

    protected function generateForm() {

        $blnSubmit = $this->isSubmitted();
        $strTemplate = 'fre_form';
        $arrEntity = (new \Alnv\FrontendEditingBundle\Library\Form())->getEntityByAlias($this->strAlias);
        $arrFields = (new \Alnv\FrontendEditingBundle\Library\Form())->getFormFieldsByFormId($this->strActiveForm, $this->strAlias);

        $arrTemplateData = [
            'submitId' => $this->getSubmitId(),
            'buttons' => $this->arrSettings['submitButtons'],
            'entity' => $arrEntity ?: [],
            'fields' => []
        ];
        $objTemplate = new \FrontendTemplate($strTemplate);

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

        if ($blnSubmit) {
            switch ($this->strSubmitType) {
                case 'save':
                    $strCurrentAlias = $this->save();
                    $this->redirectTo($strCurrentAlias);
                    break;
                case 'back':
                    $this->redirectBack();
                    break;
                case 'changeNsave':
                case 'saveNback':
                    $this->save();
                    $this->redirectBack();
                    break;
                case 'saveNcreate':
                    $this->save();
                    // todo
                    break;
            }
        }

        $objTemplate->setData($arrTemplateData);
        $this->Template->formTpl = $objTemplate->parse();
    }

    protected function save() {

        $arrEntity = (new \Alnv\FrontendEditingBundle\Library\Form())->createEntityByAliasAndFormId($this->strAlias, $this->strActiveForm, $this->getMemberId());
        $arrFields = (new \Alnv\FrontendEditingBundle\Library\Form())->getRawFormFieldsByFormId($this->strActiveForm);

        foreach ($this->arrSubmitted as $strName => $varValue) {
            $objField = $arrFields[$strName];
            (new \Alnv\FrontendEditingBundle\Library\Form())->setValue($varValue, $objField, $arrEntity['id']);
        }

        if (!$arrEntity['status']) {
            (new \Alnv\FrontendEditingBundle\Library\Form())->setStatus($this->arrSettings['status'], $arrEntity['id']);
        }
        if ($arrEntity['status'] && $this->arrSettings['status'] != $this->startStatus) {
            (new \Alnv\FrontendEditingBundle\Library\Form())->setStatus($this->arrSettings['status'], $arrEntity['id']);
        }

        return $arrEntity['alias'];
    }

    protected function redirectTo($strAlias='') {

        global $objPage;

        \Controller::redirect($objPage->getFrontendUrl(($strAlias?'/'.$strAlias:'')));
    }

    protected function redirectBack() {

        global $objPage;

        \Controller::redirect($objPage->getFrontendUrl());
    }

    protected function isSubmitted() {

        $strSubmitId = \Input::post('FORM_SUBMIT');
        $this->strSubmitType = null;

        if (!$strSubmitId) {
            return false;
        }

        if (\Input::post('changeNsave') !== null) {
            $this->strSubmitType = 'changeNsave';
            $this->arrSettings['status'] = \Input::post('changeNsave');
        }

        foreach ($this->arrSettings['submitButtons'] as $strId => $strLabel) {
            if (\Input::post($strId) === $strSubmitId) {
                $this->strSubmitType = $strId;
            }
        }

        if (!$this->strSubmitType) {
            return false;
        }

        return true;
    }

    protected function getSubmitId() {

        return 'form-' . $this->id;
    }

    protected function setSettings() {

        $this->arrSettings['saveMemberId'] = $this->addMemberPermissions && $this->addMemberId;
        $this->arrSettings['showForm'] = $_GET['auto_item'] || $this->disableList;
        $this->arrSettings['submitButtons'] = (new \Alnv\FrontendEditingBundle\Library\DataContainer())->getSubmitByChoice($this->submitButtons);
        $this->arrSettings['status'] = $this->startStatus ?: 0;
        $this->arrSettings['titleColumn'] = $this->titleColumn ?: '-';
        $this->arrSettings['titleHeadlineColumn'] = $this->titleHeadlineColumn ?: '-';
    }

    protected function getMemberId() {

        if (!$this->arrSettings['saveMemberId']) {
            return null;
        }

        if (!FE_USER_LOGGED_IN) {
            return 0;
        }

        $objMember = \FrontendUser::getInstance();
        return $objMember->id;
    }
}