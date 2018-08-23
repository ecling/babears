<?php 
class Tm_CustomLogo_Helper_Data extends Mage_Core_Helper_Data
{
	const XML_PATH_ENABLED = 'customlogo/customlogo_set/logo_status';
    const XML_PATH_TYPE = 'customlogo/customlogo_set/logo_type';
    const XML_PATH_UPLOAD = 'customlogo/customlogo_set/logo';
    const XML_PATH_ALT = 'customlogo/customlogo_set/logo_alt';
    const XML_PATH_DESC = 'customlogo/customlogo_set/logo_desc';
	
	public function getCfgGroup($group, $storeId = NULL)
    {
		if ($storeId)
			return Mage::getStoreConfig('customlogo/' . $group, $storeId);
		else
			return Mage::getStoreConfig('customlogo/' . $group);
    }
	
	public function getCfg($optionString, $storeCode = NULL)
    {
        return Mage::getStoreConfig('customlogo/' . $optionString, $storeCode);
    }
	
	/**
	 * Deprecated: old methods - for backward compatibility
	 */
	public function getCfgOld($optionString, $storeCode = NULL)
    {
        return $this->getCfg($optionString, $storeCode);
    }
	
	public function isEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED, $store);
    }
	
    public function getLogoType($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_TYPE, $store);
    }
	
    public function getLogoUpload($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_UPLOAD, $store);
    }
	
    public function getLogoAlt($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ALT, $store);
    }
	
    public function getLogoDesc($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_DESC, $store);
    }
}
?>