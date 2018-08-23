<?php

class Tm_LiveSearch_Block_Autocomplete extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    public function getSuggestData()
    {
        $suggestCollection = Mage::getModel('livesearch/query')->getSuggestCollection();

        return $suggestCollection;
    }
}
?>