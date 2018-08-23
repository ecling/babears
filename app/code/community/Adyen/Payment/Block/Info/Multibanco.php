<?php

class Adyen_Payment_Block_Info_Multibanco extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('adyen/info/multibanco.phtml');
    }
}
