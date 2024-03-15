<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\StringUtil;
use Contao\Validator;

class Helpers
{

    public static function makeArrayReadable($arrArray): string
    {

        $strValue = json_encode($arrArray);
        $strValue = str_replace('"', '', $strValue);
        $strValue = str_replace('[', '', $strValue);
        $strValue = str_replace(']', '', $strValue);
        $strValue = str_replace('{', '', $strValue);
        $strValue = str_replace('}', '', $strValue);
        $strValue = str_replace('"', '', $strValue);
        $strValue = str_replace(',', '<br>', $strValue);

        return str_replace(':', ': ', $strValue);
    }

    public static function getDropzoneValue($varValue): array
    {

        $arrReturn = [];
        $arrFiles = [];

        if (is_string($varValue) && $varValue) {
            $arrFiles = [$varValue];
        }

        if (is_array($varValue) && !empty($varValue)) {
            $arrFiles = $varValue;
        }

        foreach ($arrFiles as $strValue) {

            if (Validator::isBinaryUuid($strValue)) {
                $arrReturn[] = StringUtil::binToUuid($strValue);
            } elseif (Validator::isUuid($strValue)) {
                $arrReturn[] = $strValue;
            }
        }

        return $arrReturn;
    }
}