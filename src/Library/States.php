<?php

namespace Alnv\FrontendEditingBundle\Library;

class States {

    public function changeState($strEntityId, $strStateId, $arrSubmits) {

        if (!$strStateId) {
            return null;
        }

        $objState = \Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strStateId);

        if (!$objState->numRows) {
            return null;
        }

        if ($objState->notification) {

            $arrTokens = [
                'admin_email' => \Config::get('adminEmail')
            ];

            foreach ($arrTokens as $strField => $varValue) {
                $arrTokens['form_'.$strField] = $varValue;
            }

            $objNotification = \NotificationCenter\Model\Notification::findByPk($objState->notification);
            if ($objNotification === null) {
                return null;
            }
            $objNotification->send($arrTokens);
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