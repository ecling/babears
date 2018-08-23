<?php

class Tm_LiveSearch_Block_Include extends Mage_Core_Block_Abstract
{
	const XML_PATH_ENABLED = 'livesearch/livesearch_set/active';
	
	 public function isEnabled($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED, $store);
    }
	
	public function _prepareLayout()
    {          
        if ($this->isEnabled()){

            $layout = $this->getLayout();
            $head = $layout->getBlock('head');
			$layout->getBlock('top.search')->setTemplate('tm/livesearch/catalogsearch/form.mini.phtml');
            $head->addItem('skin_js', 'js/tm/livesearch/livesearch.js');
            $head->addItem('skin_css', 'css/tm/livesearch/livesearch.css');
			
		} else return;   
    }
}
?>