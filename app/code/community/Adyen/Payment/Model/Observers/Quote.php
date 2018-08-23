<?php
/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Sander Mangel <sander@sandermangel.nl>
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_Observers_Quote
{
    /**
     * call all observer methods listening to the sales_quote_item_set_product event
     *
     * @param Varien_Event_Observer $observer
     */
    public function salesQuoteItemSetProduct(Varien_Event_Observer $observer)
    {
        $this->_salesQuoteItemSetAttributes($observer);
    }

    /**
     * set the quote item values based on product attribute values
     *
     * @param Varien_Event_Observer $observer
     */
    protected function _salesQuoteItemSetAttributes(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        $quoteItem = $observer->getQuoteItem();
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getProduct();

        $quoteItem->setAdyenPreOrder((bool)$product->getAdyenPreOrder()); // set if product is pre order
    }
}
