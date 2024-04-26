<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;
use Contao\FormModel;
use Contao\Config;

PaletteManipulator::create()
    ->addLegend('feEntities_legend', 'contact_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('feEntities', 'feEntities_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member');

$GLOBALS['TL_DCA']['tl_member']['fields']['feEntities'] = [
    'inputType' => 'dcaWizard',
    'foreignTable' => 'tl_entity',
    'foreignField' => 'member',
    'eval' => [
        'orderField' => 'created_at DESC',
        'hideButton' => true,
        'showOperations' => true,
        'listCallback' => ['tl_member_fe_entities', 'generateWizardList'],
    ]
];

class tl_member_fe_entities
{

    public function generateWizardList($objRecords, $strId, $widget)
    {

        $strReturn = '
            <table class="tl_listing showColumns">
                <thead>
                    <td class="tl_folder_tlist">' . $GLOBALS['TL_LANG']['tl_entity']['created_at'][0] . '</td>
                    <td class="tl_folder_tlist">' . $GLOBALS['TL_LANG']['tl_entity']['form'][0] . '</td>
                    <td class="tl_folder_tlist">' . $GLOBALS['TL_LANG']['tl_entity']['status'][0] . '</td>
                    <td class="tl_folder_tlist tl_right_nowrap"></td>
                </thead>
            <tbody>';
        while ($objRecords->next()) {
            $arrRow = $objRecords->row();
            $strReturn .= '
                <tr>
                    <td class="tl_file_list">' . date(Config::get('datimFormat'), $objRecords->created_at) . '</td>
                    <td class="tl_file_list">' . $this->getFormTitle($objRecords->pid) . '</td>
                    <td class="tl_file_list">' . $this->getStatus($objRecords->status) . '</td>
                    <td class="tl_file_list tl_right_nowrap">' . $widget->generateRowOperation('edit', $arrRow) . $widget->generateRowOperation('show', $arrRow) . '</td>
                </tr>';
        }
        $strReturn .= '</tbody></table>';
        return $strReturn;
    }

    protected function getStatus($strId)
    {

        $objState = Database::getInstance()->prepare('SELECT * FROM tl_states WHERE id=?')->limit(1)->execute($strId);

        return $objState->name ?: '-';
    }

    protected function getFormTitle($strId): string
    {

        $objGroup = Database::getInstance()->prepare('SELECT * FROM tl_entity_group WHERE id=?')->limit(1)->execute($strId);

        if (!$objGroup->numRows) {
            return '-';
        }

        $objForm = FormModel::findByPk($objGroup->form);

        return $objForm ? $objForm->title : '-';
    }
}
