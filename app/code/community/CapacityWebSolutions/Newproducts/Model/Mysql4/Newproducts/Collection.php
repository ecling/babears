<?php
/***************************************************************************
 Extension Name	: New Products
 Extension URL	: http://www.magebees.com/magento-new-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_newproducts_Model_Mysql4_newproducts_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('newproducts/newproducts');
    }
	
}