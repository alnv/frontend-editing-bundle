<?php

namespace Alnv\FrontendEditingBundle\Library;

class FeNotification {

    public function send($strNotificationId, $strEntityId) {

        if (!$strNotificationId) {
            return;
        }

        $arrTokens = [
            'admin_email' => \Config::get('adminEmail')
        ];

        $this->setFormTokens($strEntityId, $arrTokens);
        $this->setMemberTokens($arrTokens);
        $objNotification = \NotificationCenter\Model\Notification::findByPk($strNotificationId);
        if ($objNotification) {
            $objNotification->send($arrTokens);
        }
    }

    protected function setFormTokens($strEntityId, &$arrTokens) {

        $objValues = \Database::getInstance()->prepare('SELECT * FROM tl_entity_value WHERE pid=?')->execute($strEntityId);

        while ($objValues->next()) {

            $objField = \FormFieldModel::findByPk($objValues->field);

            if (!$objField) {
                continue;
            }

            if (!$objField->name) {
                continue;
            }

            $varValues = \StringUtil::deserialize($objValues->varValue);
            if (is_array($varValues)) {
                $arrValues = [];
                foreach ($varValues as $strValue) {
                    if (is_array($strValue)) {
                        $strValue = (\Alnv\FrontendEditingBundle\Library\Helpers::makeArrayReadable($strValue));
                    }
                    if (\Validator::isUuid($strValue) || \Validator::isBinaryUuid($strValue)) {
                        if ($objFile = \FilesModel::findByUuid($strValue)) {
                            $strValue = $objFile->path;
                        }
                    }
                    $arrValues[] = $strValue;
                }
                $varValues = implode(', ', $arrValues);
            }

            $arrTokens['form_'.$objField->name] = $varValues;
        }
    }

    protected function setMemberTokens(&$arrTokens, $strMemberId=null) {

        if (!$strMemberId) {
            $strMemberId = \FrontendUser::getInstance()->id;
        }

        if (!$strMemberId) {
            return null;
        }

        $objMember = \MemberModel::findByPk($strMemberId);
        if (!$objMember) {
            return null;
        }

        foreach ($objMember->row() as $strField => $strValue) {
            $arrTokens['member_'.$strField] = $strValue;
        }
    }
}
