<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_ThemeSetting_Model_System_Config_Backend_Color extends Mage_Core_Model_Config_Data
{
	public function save()
	{
		$v = $this->getValue();
		if ($v == 'rgba(0, 0, 0, 0)')
		{
			$this->setValue('transparent');
		}
		return parent::save();
	}
}
