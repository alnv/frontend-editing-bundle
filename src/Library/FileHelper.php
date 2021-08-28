<?php

namespace Alnv\FrontendEditingBundle\Library;

class FileHelper {

    public static function sendFileToBrowser() {

        $strFile = \Input::get('file');

        if (!$strFile) {
            return null;
        }

        if (!is_array($GLOBALS['DOWNLOADABLE-FILES']) || empty($GLOBALS['DOWNLOADABLE-FILES'])) {
            return null;
        }

        foreach ($GLOBALS['DOWNLOADABLE-FILES'] as $strPath) {
            if ($strFile == $strPath || \dirname($strFile) == $strPath) {
                if (isset($GLOBALS['TL_HOOKS']['beforeDownload']) && is_array($GLOBALS['TL_HOOKS']['beforeDownload'])) {
                    foreach ($GLOBALS['TL_HOOKS']['beforeDownload'] as $arrCallback) {
                        (new $arrCallback[0])->{$arrCallback[1]}($strFile);
                    }
                }
                \Controller::sendFileToBrowser($strFile);
            }
        }
    }

    public static function getFiles($strUuids, $arrOrder) {

        $arrFiles = [];
        $arrValues = \StringUtil::deserialize($strUuids, true);
        $objFiles = \FilesModel::findMultipleByUuids($arrValues);

        if ($objFiles === null) {
            return $arrFiles;
        }

        $objContainer = \System::getContainer();
        $allowedDownload = \StringUtil::trimsplit(',', strtolower(\Config::get('allowedDownload')));

        while ($objFiles->next()) {

            if ( isset($arrFiles[$objFiles->path]) || !file_exists(\System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFiles->path)) {
                continue;
            }

            if ($objFiles->type == 'file') {

                $objFile = new \File($objFiles->path);

                if (!\in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename)) {
                    continue;
                }

                $GLOBALS['DOWNLOADABLE-FILES'][] = $objFiles->path;
                $arrMeta = \Frontend::getMetaData($objFiles->meta, $objContainer->get('request_stack')->getCurrentRequest()->getLocale());
                if ($arrMeta['title'] == '') {
                    $arrMeta['title'] = \StringUtil::specialchars($objFiles->basename);
                }

                $strHref = \Environment::get('request');
                if (isset($_GET['file'])) {
                    $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                }
                if (isset($_GET['cid'])) {
                    $strHref = preg_replace('/(&(amp;)?|\?)cid=\d+/', '', $strHref);
                }
                $strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . \System::urlEncode($objFiles->path);

                $arrFiles[$objFiles->path] = [
                    'id' => $objFiles->id,
                    'uuid' => \StringUtil::binToUuid($objFiles->uuid),
                    'name' => $objFile->basename,
                    'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
                    'link' => $arrMeta['title'],
                    'caption' => $arrMeta['caption'],
                    'href' => $strHref,
                    'icon' => \Image::getPath($objFile->icon),
                    'mime' => $objFile->mime,
                    'meta' => $arrMeta,
                    'extension' => $objFile->extension,
                    'path' => $objFile->dirname,
                    'urlpath' => $objFile->path,
                    'filesize'  => \Controller::getReadableSize($objFile->filesize)
                ];
            } else {

                $objSubfiles = \FilesModel::findByPid($objFiles->uuid, ['order' => 'name']);

                if ($objSubfiles === null) {
                    continue;
                }

                while ($objSubfiles->next()) {

                    if ($objSubfiles->type == 'folder') {
                        continue;
                    }

                    $objFile = new \File($objSubfiles->path);
                    $GLOBALS['DOWNLOADABLE-FILES'][] = $objFiles->path;

                    if (!\in_array($objFile->extension, $allowedDownload) || preg_match('/^meta(_[a-z]{2})?\.txt$/', $objFile->basename)) {
                        continue;
                    }

                    $arrMeta = \Frontend::getMetaData($objSubfiles->meta, $objContainer->get('request_stack')->getCurrentRequest()->getLocale());
                    if ($arrMeta['title'] == '') {
                        $arrMeta['title'] = \StringUtil::specialchars($objFile->basename);
                    }

                    $strHref = \Environment::get('request');
                    if (preg_match('/(&(amp;)?|\?)file=/', $strHref)) {
                        $strHref = preg_replace('/(&(amp;)?|\?)file=[^&]+/', '', $strHref);
                    }
                    $strHref .= (strpos($strHref, '?') !== false ? '&amp;' : '?') . 'file=' . \System::urlEncode($objSubfiles->path);

                    $arrFiles[$objSubfiles->path] = [
                        'id' => $objSubfiles->id,
                        'uuid' => \StringUtil::binToUuid($objSubfiles->uuid),
                        'name' => $objFile->basename,
                        'title' => \StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $objFile->basename)),
                        'link' => $arrMeta['title'],
                        'caption' => $arrMeta['caption'],
                        'href' => $strHref,
                        'filesize' => \Controller::getReadableSize($objFile->filesize),
                        'icon' => \Image::getPath($objFile->icon),
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
                $objFile = \FilesModel::findByUuid($strUuid);
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