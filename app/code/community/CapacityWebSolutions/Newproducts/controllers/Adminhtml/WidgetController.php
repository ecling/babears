<?php
/***************************************************************************
	@extension	: New Products.
	@copyright	: Copyright (c) 2015 Capacity Web Solutions.
	( http://www.capacitywebsolutions.com )
	@author		: Capacity Web Solutions Pvt. Ltd.
	@support	: magento@capacitywebsolutions.com	
***************************************************************************/
class CapacityWebSolutions_Newproducts_Adminhtml_WidgetController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Prepare block for chooser
     *
     * @return void
     */
    public function chooserAction()
    {
		$request = $this->getRequest();
		$block = $this->getLayout()->createBlock(
			'newproducts/promo_widget_chooser_sku', 'promo_widget_chooser_sku',array('js_form_object' => $request->getParam('form'),
		));
               
        if ($block) {
            $this->getResponse()->setBody($block->toHtml());
        }
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('cws');
    }

   
}
