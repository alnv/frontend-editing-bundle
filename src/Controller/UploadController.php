<?php

namespace Alnv\FrontendEditingBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\System;
use Contao\Widget;
use Contao\FormUpload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Contao\Input;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\FormFieldModel;

#[Route(path: 'dropzone', name: 'upload-controller', defaults: ['_scope' => 'frontend'])]
class UploadController extends AbstractController
{

    #[Route(path: '/upload/{id}', methods: ["POST"])]
    public function upload($id): JsonResponse
    {

        $this->container->get('contao.framework')->initialize();

        $objField = FormFieldModel::findByPk($id);

        if (!$objField) {
            \header("HTTP/1.0 400 Bad Request");
            echo ($GLOBALS['TL_LANG']['MSC']['uploadMessageTryAgain'] ?? '');
            exit;
        }

        if ($objField->multiple && count($this->getUploadsParam()) >= (int)$objField->mSize) {
            \header("HTTP/1.0 400 Bad Request");
            echo ($GLOBALS['TL_LANG']['MSC']['uploadLimit'] ?? '');
            exit;
        }

        $arrAttribute = Widget::getAttributesFromDca([
            'inputType' => 'fileTree',
            'eval' => $objField->row(),
        ], $objField->name, null, $objField->name);

        $objUpload = new FormUpload($arrAttribute);
        $objUpload->validate();

        if ($objUpload->hasErrors()) {
            \header("HTTP/1.0 400 Bad Request");
            echo ($objUpload->getErrorAsString() ?: $GLOBALS['TL_LANG']['MSC']['uploadGeneralError']);
            exit;
        } else {
            $this->clearUploads($objField);
        }

        return new JsonResponse([
            'file' => $objUpload->value
        ]);
    }

    #[Route(path: '/file/title', methods: ["POST"])]
    public function changeMetaTitle(): JsonResponse
    {

        $this->container->get('contao.framework')->initialize();

        $objFile = FilesModel::findByUuid(Input::post('uuid'));

        if (!$objFile || !Input::post('language')) {
            return new JsonResponse(['done' => false]);
        }

        $arrMeta = StringUtil::deserialize($objFile->meta, true);

        if (!isset($arrMeta[Input::post('language')])) {
            $arrMeta[Input::post('language')] = [];
        }

        $arrMeta[Input::post('language')]['title'] = Input::post('title') ?: '';

        $objFile->meta = serialize($arrMeta);
        $objFile->save();

        return new JsonResponse(['done' => true, 'meta' => $arrMeta]);
    }

    #[Route(path: '/remove/{uuid}', methods: ["POST"])]
    public function remove($uuid): JsonResponse
    {

        $objFile = FilesModel::findByUuid($uuid);
        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');

        if ($objFile) {
            unlink($strRootDir . '/' . $objFile->path);
            $objFile->delete();
        }

        return new JsonResponse([]);
    }

    protected function clearUploads($objField): void
    {

        $strRootDir = System::getContainer()->getParameter('kernel.project_dir');
        $varUploads = $this->getUploadsParam();
        if (!$objField->multiple && !empty($varUploads)) {
            foreach ($varUploads as $strUuid) {
                $objFile = FilesModel::findByUuid($strUuid);
                if ($objFile) {
                    unlink($strRootDir . '/' . $objFile->path);
                    $objFile->delete();
                }
            }
        }
    }

    protected function getUploadsParam(): array
    {

        $varUploads = Input::post('uploads');
        if (!\is_array($varUploads) && !empty($varUploads)) {
            $varUploads = [$varUploads];
        }

        return ($varUploads ?: []);
    }

    protected function getUpload($strName): array
    {

        $arrUpload = $_SESSION['FILES'][$strName] ?? [];
        $objFile = FilesModel::findByUuid(($_SESSION['FILES'][$strName]['uuid'] ?? ''));

        if ($objFile) {
            $arrUpload['path'] = $objFile->path;
        }

        return $arrUpload;
    }
}