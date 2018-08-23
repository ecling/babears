<?php

class Tm_LiveSearch_AjaxController extends Mage_Core_Controller_Front_Action
{
    public function suggestAction()
    {
        $this->loadLayout();
        $this->renderLayout();

        //Zend_Debug::dump($this->getLayout()->getUpdate()->getHandles());
    }
}