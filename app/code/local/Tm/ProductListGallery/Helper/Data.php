<?php 

class Tm_Productlistgallery_Helper_Data extends Mage_Core_Helper_Data
{
	

	public function getGridData($key)
	{
		if (!isset($key)) {			
			return false;
		} 
		return $this->getConfigValue('grid', $key);
	    
	}

	public function getListData($key)
	{
		if (!isset($key)) {			
			return false;
		} 
		return $this->getConfigValue('list', $key);
	    
	}

	public function getHomeGridData($key)
	{
		if (!isset($key)) {			
			return false;
		} 
		return $this->getConfigValue('home_grid', $key);
	    
	}

	public function getHomeListData($key)
	{
		if (!isset($key)) {			
			return false;
		} 
		return $this->getConfigValue('home_list', $key);
	    
	}

	protected function getConfigValue($group, $key)
    {
    	if($key == 'active'){
    		return Mage::getStoreConfigFlag('productlistgallery/'. $group .'/' . $key, $this->getStore());	
    	}

    	return Mage::getStoreConfig('productlistgallery/'. $group .'/' . $key,  $this->getStore());
    }

    private function getStore()
	{
		return Mage::app()->getStore();
	}


}