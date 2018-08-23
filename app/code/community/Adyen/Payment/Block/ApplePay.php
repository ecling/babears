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
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Block_ApplePay extends Mage_Core_Block_Template
{

    /**
     * @return bool
     */
    public function hasApplePayEnabled()
    {
        if(!Mage::helper('adyen')->getConfigData("active", "adyen_apple_pay", null)) {
            return false;
        }

        // if user is not logged in and quest checkout is not enabled don't show the button
        if(!Mage::getSingleton('customer/session')->isLoggedIn() &&
            !Mage::helper('adyen')->getConfigData('allow_quest_checkout', 'adyen_apple_pay'))
        {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSubTotal()
    {
        $subtotal = array();

        if ($this->getProduct() && $this->getProduct()->getTypeId() === Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
            $product = $this->_getData('product');
            if (!$product) {
                $product = Mage::registry('product');
                $subtotal['label'] = $product->getName();
                $subtotal['amount'] = $product->getFinalPrice();
                $subtotal['productId'] = $product->getId();
            }
        } else if (Mage::getSingleton('checkout/session')->getQuote()->getItemsCount() > 0) {
            $subtotal['label'] = $this->__('Grand Total');
            $subtotal['amount'] = $this->getSubtotalInclTax();
            $subtotal['productId'] = 0;
        }
        return $subtotal;
    }

    /**
     * Re-using subtotal calculation from Mage_Checkout_Block_Cart_Sidebar
     *
     * @return decimal subtotal amount including tax
     */
    public function getSubtotalInclTax()
    {
        $cart = Mage::getModel('checkout/cart');
        $subtotal = 0;
        $totals = $cart->getQuote()->getTotals();
        $config = Mage::getSingleton('tax/config');
        if (isset($totals['subtotal'])) {
            if ($config->displayCartSubtotalBoth() || $config->displayCartSubtotalInclTax()) {
                $subtotal = $totals['subtotal']->getValueInclTax();
            } else {
                $subtotal = $totals['subtotal']->getValue();
                if (isset($totals['tax'])) {
                    $subtotal+= $totals['tax']->getValue();
                }
            }
        }
        return $subtotal;
    }

    /**
     * Retrieve product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::registry('product');
    }

    /**
     * @return array
     */
    public function getShippingMethods()
    {
        $product = $this->getProduct();

        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();

            // check if address is already chosen in the checkout if os use this otherwise use the default shipping
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $shippingAddressId = $quote->getShippingAddress()->customer_address_id;

            if(!$shippingAddressId > 0) {
                $shippingAddressId = $customer->getDefaultShipping();
            }

            if ($shippingAddressId) {
                $shippingAddress = Mage::getModel('customer/address')->load($shippingAddressId);
                $country = $shippingAddress->getCountryId();

                // if it is a product retrieve shippping methods and calculate shippingCosts on this product
                if ($product) {
                    $shippingCosts = $this->calculateShippingCosts($product->getId(), $country, Mage::app()->getStore()->getId());
                    return $shippingCosts;
                }

                // it is not a product so this is on the shopping cart retrieve shipping methods and calculate shipping costs on the cart
                $zipcode = $shippingAddress->getPostcode();

                $cart = Mage::getSingleton('checkout/cart');
                $address = $cart->getQuote()->getShippingAddress();
                $address->setCountryId($country)
                    ->setPostcode($zipcode)
                    ->setCollectShippingrates(true);
                $cart->save();

                // Find if our shipping has been included.
                $rates = $address->collectShippingRates()
                    ->getGroupedAllShippingRates();

                $costs = array();
                foreach ($rates as $carrier) {
                    foreach ($carrier as $rate) {

                        $costs[$rate->getCode()] = array(
                            'title' => trim($rate->getCarrierTitle()),
                            'price' => $rate->getPrice()
                        );

                    }
                }
                return $costs;
            }
        } else {
            if($product) {
                $country = "";
                $shippingCosts = $this->calculateShippingCosts($product->getId(), $country, Mage::app()->getStore()->getId());
                return $shippingCosts;
            }
        }

        return array();
    }

    /**
     * @param $productId
     * @param $country
     * @param int $storeId
     * @param int $qty
     * @return array
     */
    public function calculateShippingCosts($productId, $country, $storeId = 1, $qty = 1)
    {
        $product = Mage::getModel('catalog/product')->load($productId);
//        $item = Mage::getModel('sales/quote_item')->setProduct($product)->setQty(1);
        $item = Mage::getModel('sales/quote_item')->setProduct($product);
        $store = Mage::getModel('core/store')->load($storeId);

        $request = Mage::getModel('shipping/rate_request')
            ->setAllItems(array($item))
            ->setDestCountryId($country)
            ->setPackageValue($product->getFinalPrice())
            ->setPackageValueWithDiscount($product->getFinalPrice())
            ->setPackageWeight($product->getWeight())
            ->setPackageQty($qty)
            ->setPackagePhysicalValue($product->getFinalPrice())
            ->setFreeMethodWeight(0)
            ->setStoreId($store->getId())
            ->setWebsiteId($store->getWebsiteId())
            ->setFreeShipping(0)
            ->setBaseCurrency($store->getBaseCurrency())
            ->setBaseSubtotalInclTax($product->getFinalPrice());

        $model = Mage::getModel('shipping/shipping')->collectRates($request);
        $costs = array();

        foreach($model->getResult()->getAllRates() as $shippingRate) {

            $rate = Mage::getModel('sales/quote_address_rate')
                ->importShippingRate($shippingRate);

            $costs[$rate->getCode()] = array(
                'title' => trim($rate->getCarrierTitle()),
                'price' => $rate->getPrice()
            );
        }
        return $costs;
    }

    /**
     * @return array
     */
    public function getCustomerData()
    {
        $customCustomerData = array('isLoggedIn' => Mage::getSingleton('customer/session')->isLoggedIn());

        if (Mage::getSingleton('customer/session')->isLoggedIn()) {

            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $lastName = trim($customer->getMiddlename() . " " . $customer->getLastname());
            $customCustomerData['givenName'] = $customer->getFirstname();
            $customCustomerData['familyName'] = $lastName;
            $customCustomerData['emailAddress'] = $customer->getEmail();
            $billingAddressId = $customer->getDefaultBilling();

            // only add billingAddress if he has one and is not in the latest step of the checkout
            if ($billingAddressId && !$this->onReviewStep()) {

                $billingAddress = Mage::getModel('customer/address')->load($billingAddressId);
                $lastName = trim($billingAddress->getMiddlename() . " " . $billingAddress->getLastname());
                $countryName = Mage::app()->getLocale()->getCountryTranslation($billingAddress->getCountryId());

                // get state name
                $administrativeArea = "";
                if($billingAddress->getRegionId() > 0) {
                    $region = Mage::getModel('directory/region')->load($billingAddress->getRegionId());
                    if($region) {
                        $administrativeArea = $region->getCode(); //CA
                    }
                } else {
                    $administrativeArea = $billingAddress->getRegion(); // open field
                }

                $customCustomerData['billingContact'] = array(
                    'emailAddress' => $customer->getEmail(),
                    'phoneNumber' => $billingAddress->getTelephone(),
                    'familyName' => $lastName,
                    'givenName' => $billingAddress->getFirstname(),
                    'addressLines' =>  $billingAddress->getStreet(),
                    'locality' => $billingAddress->getCity(),
                    'postalCode' => $billingAddress->getPostcode(),
                    'administrativeArea' => $administrativeArea, // state
                    'country' => $countryName,
                    'countryCode' => $billingAddress->getCountryId()
                );
            }

            $shippingAddressId = $customer->getDefaultShipping();

            // only add shippingAddressId if he has one and is not in the latest step of the checkout
            if ($shippingAddressId && !$this->onReviewStep()) {
                $shippingAddress = Mage::getModel('customer/address')->load($shippingAddressId);

                $lastName = trim($shippingAddress->getMiddlename() . " " . $shippingAddress->getLastname());
                $countryName = Mage::app()->getLocale()->getCountryTranslation($shippingAddress->getCountryId());

                // get state name
                $administrativeArea = "";
                if($shippingAddress->getRegionId() > 0) {
                    $region = Mage::getModel('directory/region')->load($shippingAddress->getRegionId());
                    if($region) {
                        $administrativeArea = $region->getCode(); //CA
                    }
                } else {
                    $administrativeArea = $shippingAddress->getRegion(); // open field
                }

                $customCustomerData['shippingContact'] = array(
                    'emailAddress' => $customer->getEmail(),
                    'phoneNumber' => $shippingAddress->getTelephone(),
                    'familyName' => $lastName,
                    'givenName' => $shippingAddress->getFirstname(),
                    'addressLines' =>  $shippingAddress->getStreet(),
                    'locality' => $shippingAddress->getCity(),
                    'postalCode' => $shippingAddress->getPostcode(),
                    'administrativeArea' => $administrativeArea, // state
                    'country' => $countryName,
                    'countryCode' => $shippingAddress->getCountryId()
                );
            }
        }
        return $customCustomerData;
    }

    /**
     * @return mixed
     */
    public function getMerchantIdentifier()
    {
        return Mage::helper('adyen')->getApplePayMerchantIdentifier();
    }

    /**
     * Only possible if quest checkout is turned off
     *
     * @return bool
     */
    public function optionToChangeAddress()
    {
        if (!$this->onReviewStep()) {
            if(!Mage::helper('adyen')->getConfigData('allow_quest_checkout', 'adyen_apple_pay')) {
                return Mage::helper('adyen')->getConfigData('change_address', 'adyen_apple_pay');
            }
            return true;
        }
        return false;
    }

    /**
     * This is called when you are in the review step of the payment and use Apple Pay
     */
    public function getShippingMethodAmount()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getShippingAmount();
    }

    /**
     * @return bool
     */
    public function onReviewStep()
    {
        if($this->getData("reviewStep")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getShippingType()
    {
        return Mage::helper('adyen')->getConfigData('shipping_type', 'adyen_apple_pay');
    }

}