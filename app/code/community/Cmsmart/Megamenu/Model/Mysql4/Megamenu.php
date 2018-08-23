<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_Megamenu_Model_Mysql4_Megamenu extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        // Note that the adminmenutop_id refers to the key field in your database table.
        $this->_init('megamenu/megamenu', 'adminmenutop_id');
    }
}