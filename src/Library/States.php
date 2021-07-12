<?php

namespace Alnv\FrontendEditingBundle\Library;

class States {

    public function changeState($strEntityId, $strStateId) {

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

            $this->setFormTokens($strEntityId, $arrTokens);
            $this->setMemberTokens($arrTokens);
            $objNotification = \NotificationCenter\Model\Notification::findByPk($objState->notification);
            if (!$objNotification) {
                $objNotification->send($arrTokens);
            }
        }

        \Database::getInstance()->prepare('UPDATE tl_entity %s WHERE id=?')->set(['status' => $strStateId])->limit(1)->execute($strEntityId);
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