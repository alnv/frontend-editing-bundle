<?php

namespace Alnv\FrontendEditingBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Contao\Input;
use Contao\System;
use Contao\FilesModel;
use Contao\FormUpload;
use Contao\StringUtil;
use Contao\FormFieldModel;

#[Route(path: 'dropzone', name: 'upload-controller', defaults: ['_scope' => 'frontend'])]
class UploadController extends AbstractController
{

    #[Route(path: '/upload/{id}', methods: ["POST"])]
    public function upload($id): JsonResponse
    {

        $this->container->get('contao.framework')->initialize();

        $arrResponse = [
            'success' => false,
            'error' => ''
        ];

        $objField = FormFieldModel::findByPk($id);

        if (!$objField) {
            $arrResponse['error'] = $GLOBALS['TL_LANG']['MSC']['uploadMessageTryAgain'];
            return new JsonResponse($arrResponse);
        }

        if ($objField->multiple && count($this->getUploadsParam()) >= (int)$objField->mSize) {
            $arrResponse['error'] = $GLOBALS['TL_LANG']['MSC']['uploadLimit'];
            return new JsonResponse($arrResponse);
        }

        $arrAttribute = FormUpload::getAttributesFromDca([
            'inputType' => 'fileTree',
            'eval' => $objField->row(),
        ], $objField->name, null, $objField->name);

        $objUpload = new FormUpload($arrAttribute);
        $objUpload->validate();

        if ($objUpload->hasErrors()) {
            $arrResponse['error'] = $objUpload->getErrorAsString() ?: $GLOBALS['TL_LANG']['MSC']['uploadGeneralError'];
        } else {
            $this->clearUploads($objField);
            $arrResponse['success'] = true;
            $arrResponse['file'] = $this->getUpload($objField->name);
        }

        return new JsonResponse($arrResponse);
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
        if (!is_array($varUploads) && !empty($varUploads)) {
            $varUploads = [$varUploads];
        }

        return ($varUploads ?: []);
    }

    protected function getUpload($strName): array
    {

        $arrUpload = $_SESSION['FILES'][$strName];
        $objFile = FilesModel::findByUuid($_SESSION['FILES'][$strName]['uuid']);

        if ($objFile) {
            $arrUpload['path'] = $objFile->path;
        }

        return $arrUpload;
    }
}