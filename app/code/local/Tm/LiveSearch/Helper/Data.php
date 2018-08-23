<?php

class Tm_LiveSearch_Helper_Data extends Mage_CatalogSearch_Helper_Data
{
	public function getCfg($optionString, $storeCode = NULL)
    {
        return Mage::getStoreConfig('livesearch/' . $optionString, $storeCode);
    }
}