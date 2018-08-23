<?php
/***************************************************************************
 Extension Name	: Bestseller Products
 Extension URL	: http://www.magebees.com/magento-bestseller-products-extension.html
 Copyright		: Copyright (c) 2015 MageBees, http://www.magebees.com
 Support Email	: support@magebees.com 
 ***************************************************************************/
class CapacityWebSolutions_Bestseller_Model_Mysql4_Bestseller extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('bestseller/bestseller', 'bestseller_id');
    }
}