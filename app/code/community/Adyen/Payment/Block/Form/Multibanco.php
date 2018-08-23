<?php

class Adyen_Payment_Block_Form_Multibanco extends Adyen_Payment_Block_Form_Abstract
{
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('adyen/form/multibanco.phtml');
    }
}
