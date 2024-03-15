<?php

namespace Alnv\FrontendEditingBundle\Inserttags;

class IgnoreTags
{

    public function replace($strFragments)
    {

        if (in_array($strFragments, ['filesize', 'maxFilesize', 'statusCode'])) {
            return '{{' . $strFragments . '}}';
        }

        return false;
    }
}