<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\Controller;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;

class States extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function changeState($strEntityId, $strStateId)
    {

        if (!$strStateId) {
            return null;
        }

        $objState = Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStateId);

        if (!$objState->numRows) {
            return null;
        }

        (new FeNotification())->send($objState->notification, $strEntityId);

        if (isset($GLOBALS['TL_HOOKS']['changeState']) && is_array($GLOBALS['TL_HOOKS']['changeState'])) {
            foreach ($GLOBALS['TL_HOOKS']['changeState'] as $arrCallback) {
                System::importStatic($arrCallback[0])->{$arrCallback[1]}($strEntityId, $strStateId, $this);
            }
        }

        Database::getInstance()->prepare('UPDATE tl_entity %s WHERE id=?')->set(['status' => $strStateId])->limit(1)->execute($strEntityId);
    }

    public function getStateExcludes($strStateId)
    {

        $objState = Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStateId);

        return StringUtil::deserialize($objState->excludes, true);
    }

    public function isAllowed($strType, $strStateId): bool
    {

        $arrExcludes = $this->getStateExcludes($strStateId);

        if (empty($arrExcludes)) {
            return true;
        }

        if (in_array($strType, $arrExcludes)) {
            return false;
        }

        return true;
    }
}