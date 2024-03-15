<?php

namespace Alnv\FrontendEditingBundle\Form;

use Contao\FilesModel;
use Contao\Widget;

class FormDropzoneUpload extends Widget
{

    protected $blnSubmitInput = true;
    protected $strTemplate = 'form_dropzone';
    protected $strPrefix = 'widget widget-dropzone';

    public function validate()
    {

        $varValue = $this->validator($this->getPost($this->strName));
        $varValue = json_decode($varValue, true);
        $arrSave = [];

        if (is_array($varValue) && !empty($varValue)) {

            foreach ($varValue as $strUuid) {
                $objFile = FilesModel::findByUuid($strUuid);
                if ($objFile) {
                    $arrSave[] = $strUuid;
                }
            }
        }

        $this->varValue = serialize($arrSave);
    }

    public function generate()
    {

        //
    }
}