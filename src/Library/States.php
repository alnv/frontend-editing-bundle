<?php

namespace Alnv\FrontendEditingBundle\Library;

class States extends \Controller {

    public function __construct() {
        parent::__construct();
    }

    public function changeState($strEntityId, $strStateId) {

        if (!$strStateId) {
            return null;
        }

        $objState = \Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStateId);

        if (!$objState->numRows) {
            return null;
        }

        (new \Alnv\FrontendEditingBundle\Library\FeNotification())->send($objState->notification, $strEntityId);

        if (isset($GLOBALS['TL_HOOKS']['changeState']) && is_array($GLOBALS['TL_HOOKS']['changeState'])) {
            foreach ($GLOBALS['TL_HOOKS']['changeState'] as $arrCallback) {
                if (is_array($arrCallback)) {
                    $this->import($arrCallback[0]);
                    $this->{$arrCallback[0]}->{$arrCallback[1]}($strEntityId, $strStateId, $this);
                }
                elseif (\is_callable($arrCallback)) {
                    $arrCallback($strEntityId, $strStateId, $this);
                }
            }
        }

        \Database::getInstance()->prepare('UPDATE tl_entity %s WHERE id=?')->set(['status' => $strStateId])->limit(1)->execute($strEntityId);
    }

    public function getStateExcludes($strStateId) {

        $objState = \Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStateId);
        return \StringUtil::deserialize($objState->excludes, true);
    }

    public function isAllowed($strType, $strStateId) {

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