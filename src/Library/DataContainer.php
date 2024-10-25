<?php

namespace Alnv\FrontendEditingBundle\Library;

use Alnv\FrontendEditingBundle\Library\Helpers;
use Contao\Controller;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;

class DataContainer
{

    public function getSubmits(): array
    {

        System::loadLanguageFile('default');

        return [
            'save' => $GLOBALS['TL_LANG']['MSC']['submitSave'],
            'back' => $GLOBALS['TL_LANG']['MSC']['submitBack'],
            'saveNback' => $GLOBALS['TL_LANG']['MSC']['submitSaveNback'],
            'saveNcreate' => $GLOBALS['TL_LANG']['MSC']['submitSaveNcreate']
        ];
    }

    public function getSubmitByChoice($varChoice): array
    {

        $arrReturn = [];
        $arrSubmits = $this->getSubmits();

        foreach (StringUtil::deserialize($varChoice, true) as $strButtonName) {
            $arrReturn[$strButtonName] = $arrSubmits[$strButtonName] ?: '';
        }

        return $arrReturn;
    }

    public function getStatus($strStatusId): array
    {

        $objStatus = Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStatusId);
        $arrReturn = $objStatus->row();

        $arrReturn['note'] = Helpers::replaceInsertTags(StringUtil::decodeEntities(($arrReturn['note']??'')));
        $arrReturn['uploads'] = FileHelper::getFiles(($arrReturn['uploads']??''), StringUtil::deserialize(($arrReturn['uploadsOrderSRC']??''), true));

        return $arrReturn;
    }
}