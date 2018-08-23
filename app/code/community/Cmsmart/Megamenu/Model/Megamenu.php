<?php
/*
* Name Extension: Megamenu
*/
class Cmsmart_Megamenu_Model_Megamenu extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('megamenu/megamenu');
    }
}