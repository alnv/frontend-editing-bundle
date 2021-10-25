<?php

namespace Alnv\FrontendEditingBundle\Library;

class Helpers {

    public static function makeArrayReadable($arrArray) {

        $strValue = json_encode($arrArray);
        $strValue = str_replace('"', '', $strValue);
        $strValue = str_replace('[', '', $strValue);
        $strValue = str_replace(']', '', $strValue);
        $strValue = str_replace('{', '', $strValue);
        $strValue = str_replace('}', '', $strValue);
        $strValue = str_replace('"', '', $strValue);
        $strValue = str_replace(',', '<br>', $strValue);
        $strValue = str_replace(':', ': ', $strValue);

        return $strValue;
    }

    public static function getDropzoneValue($varValue) {

        $arrFiles = [];
        if (is_string($varValue) && $varValue) {
            $arrFiles = [$varValue];
        }
        if (is_array($varValue) && !empty($varValue)) {
            $arrFiles = $varValue;
        }

        $arrReturn = [];
        foreach ($arrFiles as $strValue) {
            if (\Validator::isBinaryUuid($strValue)) {
                $arrReturn[] = \StringUtil::binToUuid($strValue);
            } elseif (\Validator::isUuid($strValue)) {
                $arrReturn[] = $strValue;
            }
        }

        return $arrReturn;
    }
}