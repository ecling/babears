<?php 

class Tm_ImageZoom_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XML_PATH_CUSTOMBOX_DATA 	= 'imagezoom/general';

	public function getImagezoomData($store = null)
	{
		return Mage::getStoreConfig(self::XML_PATH_CUSTOMBOX_DATA, $store);
	}

}