<?php
class Tm_GoogleMap_Helper_Data extends Mage_Core_Helper_Abstract
{

	const XML_PATH_EMBED_MARKER = 'googlemap/embed/marker_data';
	const XML_PATH_CONTACTS_MARKER = 'googlemap/contacts/marker_data';

	private function store(){
		return Mage::app()->getStore();
	}

	public function configEmbedMarker()
	{
		return $configArray = unserialize(Mage::getStoreConfig(self::XML_PATH_EMBED_MARKER, $this->store() ));
	}

	public function configContactsMarker()
	{
		return $configArray = unserialize(Mage::getStoreConfig(self::XML_PATH_CONTACTS_MARKER, $this->store() ));
	}

	public function getConfig($xml_path)
	{
		if (!isset($xml_path)) {			
			return false;
		} 
		return Mage::getStoreConfig($xml_path, $this->store());
	}
}
?>