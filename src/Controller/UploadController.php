<?php

namespace Alnv\FrontendEditingBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Contao\CoreBundle\Controller\AbstractController;

/**
 *
 * @Route("/dropzone", defaults={"_scope"="frontend", "_token_check"=false})
 */
class UploadController extends AbstractController {

    /**
     *
     * @Route("/upload/{id}", name="dropzone-upload")
     * @Method({"POST"})
     */
    public function upload($id) {

        $this->container->get('contao.framework')->initialize();

        $arrResponse = [
            'success' => false,
            'error' => ''
        ];

        $objField = \FormFieldModel::findByPk($id);

        if (!$objField) {
            $arrResponse['error'] = $GLOBALS['TL_LANG']['MSC']['uploadMessageTryAgain'];
            return new JsonResponse($arrResponse);
        }

        if ($objField->multiple && count($this->getUploadsParam()) >= (int) $objField->mSize) {
            $arrResponse['error'] = $GLOBALS['TL_LANG']['MSC']['uploadLimit'];
            return new JsonResponse($arrResponse);
        }

        $arrAttribute = \FormFileUpload::getAttributesFromDca([
            'inputType' => 'fileTree',
            'eval' => $objField->row(),
        ], $objField->name, null, $objField->name);

        $objUpload = new \FormFileUpload($arrAttribute);
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

    /**
     *
     * @Route("/file/title", name="dropzone-changeMetaTitle")
     * @Method({"POST"})
     */
    public function changeMetaTitle() {

        $this->container->get('contao.framework')->initialize();

        $objFile = \FilesModel::findByUuid(\Input::post('uuid'));

        if (!$objFile || !\Input::post('language')) {
            return new JsonResponse(['done' => false]);
        }

        $arrMeta = \StringUtil::deserialize($objFile->meta, true);
        if (!isset($arrMeta[\Input::post('language')])) {
            $arrMeta[\Input::post('language')] = [];
        }

        $arrMeta[\Input::post('language')]['title'] = \Input::post('title') ?: '';

        $objFile->meta = serialize($arrMeta);
        $objFile->save();

        return new JsonResponse(['done' => true, 'meta' => $arrMeta]);
    }

    /**
     *
     * @Route("/remove/{uuid}", name="dropzone-remove")
     * @Method({"POST"})
     */
    public function remove($uuid) {

        $objFile = \FilesModel::findByUuid($uuid);
        if ($objFile) {
            unlink(TL_ROOT . '/' . $objFile->path);
            $objFile->delete();
        }

        return new JsonResponse([]);
    }

    protected function clearUploads($objField) {

        $varUploads = $this->getUploadsParam();
        if (!$objField->multiple && !empty($varUploads)) {
            foreach ($varUploads as $strUuid) {
                $objFile = \FilesModel::findByUuid($strUuid);
                if ($objFile) {
                    unlink(TL_ROOT . '/' . $objFile->path);
                    $objFile->delete();
                }
            }
        }
    }

    protected function getUploadsParam() {

        $varUploads = \Input::post('uploads');
        if (!is_array($varUploads) && !empty($varUploads)) {
            $varUploads = [$varUploads];
        }
        return ($varUploads?$varUploads:[]);
    }

    protected function getUpload($strName) {

        $arrUpload = $_SESSION['FILES'][$strName];
        $objFile = \FilesModel::findByUuid($_SESSION['FILES'][$strName]['uuid']);
        if ($objFile) {
            $arrUpload['path'] = $objFile->path;
        }

        return $arrUpload;
    }
}