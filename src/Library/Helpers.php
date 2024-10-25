<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;

class Helpers
{

    public static function parseSimpleTokens($strString, $arrData, $blnAllowHtml = true)
    {
        return System::getContainer()->get('contao.string.simple_token_parser')->parse($strString, $arrData, $blnAllowHtml);
    }

    public static function replaceInsertTags($strBuffer, $blnCache = true)
    {

        $parser = System::getContainer()->get('contao.insert_tag.parser');

        if ($blnCache) {
            return $parser->replace((string)$strBuffer);
        }

        return $parser->replaceInline((string)$strBuffer);
    }

    public static function get(): string
    {

        if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            return 'BE';
        }

        if (System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create(''))) {
            return 'FE';
        }

        return '';
    }

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