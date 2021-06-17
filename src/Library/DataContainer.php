<?php

namespace Alnv\FrontendEditingBundle\Library;

class DataContainer {

    public function getSubmits() {

        \System::loadLanguageFile('default');

        return [
            'save' => $GLOBALS['TL_LANG']['MSC']['submitSave'],
            'back' => $GLOBALS['TL_LANG']['MSC']['submitBack'],
            'saveNback' => $GLOBALS['TL_LANG']['MSC']['submitSaveNback'],
            'saveNcreate' => $GLOBALS['TL_LANG']['MSC']['submitSaveNcreate']
        ];
    }

    public function getSubmitByChoice($varChoice) {

        $arrReturn = [];
        $arrSubmits = $this->getSubmits();
        foreach (\StringUtil::deserialize($varChoice) as $strButtonName) {
            $arrReturn[$strButtonName] = $arrSubmits[$strButtonName] ?: '';
        }
        return $arrReturn;
    }

    public function getStatus($strStatusId) {

        $objStatus = \Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStatusId);

        return $objStatus->row();
    }
}