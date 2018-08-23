<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_ThemeSetting_Block_Adminhtml_Button_Import_Cms extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * Import static blocks
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return String
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
		$elementOriginalData = $element->getOriginalData();
		if (isset($elementOriginalData['process']))
		{
			$name = $elementOriginalData['process'];
		}
		else
		{
			return '<div>Action was not specified</div>';
		}
		
		$buttonSuffix = '';
		if (isset($elementOriginalData['label']))
			$buttonSuffix = ' ' . $elementOriginalData['label'];

		$url = $this->getUrl('themesetting/adminhtml_import/' . $name);
		
		$html = $this->getLayout()->createBlock('adminhtml/widget_button')
			->setType('button')
			->setClass('import-cms')
			->setLabel('Import' . $buttonSuffix)
			->setOnClick("setLocation('$url')")
			->toHtml();
			
        return $html;
    }
}
