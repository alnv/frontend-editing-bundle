<?php

namespace Alnv\FrontendEditingBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 *
 * @Route("/_dropzone", defaults={"_scope"="frontend", "_token_check"=false})
 */
class UploadController extends Controller {

    /**
     *
     * @Route("/upload/{id}", name="upload")
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
     * @Route("/remove/{uuid}", name="remove")
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