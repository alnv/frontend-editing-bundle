<?php

namespace Alnv\FrontendEditingBundle\Library;

use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

class FileHelper
{
    public static function sendFileToBrowser(): void
    {

        $strFile = Input::get('file');

        if (!$strFile) {
            return;
        }

        if (!is_array($GLOBALS['DOWNLOADABLE-FILES']) || empty($GLOBALS['DOWNLOADABLE-FILES'])) {
            return;
        }

        foreach ($GLOBALS['DOWNLOADABLE-FILES'] as $strPath) {
            if ($strFile == $strPath || \dirname($strFile) == $strPath) {

                if (isset($GLOBALS['TL_HOOKS']['beforeDownload']) && is_array($GLOBALS['TL_HOOKS']['beforeDownload'])) {
                    foreach ($GLOBALS['TL_HOOKS']['beforeDownload'] as $arrCallback) {
                        System::importStatic($arrCallback[0])->{$arrCallback[1]}($strFile);
                    }
                }

                Controller::sendFileToBrowser($strFile);
            }
        }
    }

    public static function getCurrentLanguage()
    {

        $objContainer = System::getContainer();

        return $objContainer->get('request_stack')->getCurrentRequest()->getLocale();
    }

    public static function getMeta($strMeta, $objFile): array
    {

        $arrMeta = Frontend::getMetaData($strMeta, static::getCurrentLanguage());

        if ($arrMeta['title'] == '') {
            $arrMeta['title'] = StringUtil::specialchars($objFile->basename);
        }

        return $arrMeta;
    }

    public static function getFiles($strUuids, $arrOrder): array
    {

        $arrFiles = [];
        $arrValues = StringUtil::deserialize($strUuids, true);
        $objFiles = FilesModel::findMultipleByUuids($arrValues);

        if ($objFiles === null) {
            return $arrFiles;
        }

        $strHref = Environment::get('request');
        $arrAllowedDownload = StringUtil::trimsplit(',', strtolower(Config::get('allowedDownload')));

        while ($objFiles->next()) {

            if (isset($arrFiles[$objFiles->path]) || !file_exists(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path)) {
                continue;
            }

            if ($objFiles->type == 'file') {

                $objFile = new File($objFiles->path);

                if (!\in_array($objFile->extension, $arrAllowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename)) {
                    continue;
                }

                $GLOBALS['DOWNLOADABLE-FILES'][] = $objFiles->path;
                $arrMeta = static::getMeta($objFiles->meta, $objFiles);

                if (($_GET['file']??'')) {
                    $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                }
                if (($_GET['cid']??'')) {
                    $strHref = preg_replace('/(&(amp;)?|\?)cid=\d+/', '', $strHref);
                }

                $strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . System::urlEncode($objFiles->path);

                $arrFiles[$objFiles->path] = [
                    'id' => $objFiles->id,
                    'uuid' => StringUtil::binToUuid($objFiles->uuid),
                    'name' => $objFile->basename,
                    'title' => StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
                    'link' => $arrMeta['title'],
                    'caption' => $arrMeta['caption'],
                    'href' => $strHref,
                    'icon' => Image::getPath($objFile->icon),
                    'mime' => $objFile->mime,
                    'meta' => $arrMeta,
                    'extension' => $objFile->extension,
                    'path' => $objFile->dirname,
                    'urlpath' => $objFile->path,
                    'filesize' => Controller::getReadableSize($objFile->filesize)
                ];
            } else {

                $objSubfiles = FilesModel::findByPid($objFiles->uuid, ['order' => 'name']);

                if ($objSubfiles === null) {
                    continue;
                }

                while ($objSubfiles->next()) {

                    if ($objSubfiles->type == 'folder') {
                        continue;
                    }

                    $objFile = new File($objSubfiles->path);
                    $GLOBALS['DOWNLOADABLE-FILES'][] = $objFiles->path;

                    if (!\in_array($objFile->extension, $arrAllowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename)) {
                        continue;
                    }

                    $arrMeta = static::getMeta($objSubfiles->meta, $objFile);

                    if (preg_match('/(&(amp;)?|\?)file=/', $strHref)) {
                        $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                    }

                    $strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . System::urlEncode($objSubfiles->path);

                    $arrFiles[$objSubfiles->path] = [
                        'id' => $objSubfiles->id,
                        'uuid' => StringUtil::binToUuid($objSubfiles->uuid),
                        'name' => $objFile->basename,
                        'title' => StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
                        'link' => $arrMeta['title'],
                        'caption' => $arrMeta['caption'],
                        'href' => $strHref,
                        'filesize' => Controller::getReadableSize($objFile->filesize),
                        'icon' => Image::getPath($objFile->icon),
                        'mime' => $objFile->mime,
                        'meta' => $arrMeta,
                        'extension' => $objFile->extension,
                        'path' => $objFile->dirname
                    ];
                }
            }
        }

        if (!empty($arrOrder)) {
            $_arrFiles = [];
            foreach ($arrOrder as $strUuid) {
                $objFile = FilesModel::findByUuid($strUuid);
                if (!$objFile) {
                    continue;
                }
                $_arrFiles[$objFile->path] = $arrFiles[$objFile->path];
            }
            return $_arrFiles;
        }

        return $arrFiles;
    }
}