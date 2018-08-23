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
 * @category    Adyen
 * @package    Adyen_Payment
 * @copyright    Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Helper_Payment extends Adyen_Payment_Helper_Data
{

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'customer_';
    const AFTERPAY_DEFAULT = "afterpay_default";

    /**
     * @param string $brandCode
     * @param bool $isConfigDemoMode
     * @param string $paymentRoutine
     * @param bool $hppOptionsDisabled
     * @return string
     */
    public function getFormUrl($brandCode, $isConfigDemoMode = false, $paymentRoutine = 'single', $hppOptionsDisabled = true)
    {
        $baseUrl = 'https://' . ($isConfigDemoMode ? 'test' : 'live') . '.adyen.com/hpp/';

        if ($paymentRoutine == 'single' && $hppOptionsDisabled) {
            return "{$baseUrl}pay.shtml";
        }

        if (!empty($brandCode) && (Mage::helper('adyen')->isOpenInvoice($brandCode) || $brandCode == "cofinoga_3xcb" || $brandCode == "cofinoga_4xcb")) {
            return "{$baseUrl}skipDetails.shtml";
        }

        return $baseUrl . ($hppOptionsDisabled ? "select" : "details") . ".shtml";
    }

    /**
     * @param array $fields
     * @param bool $isConfigDemoMode
     * @return string
     */
    public function prepareFieldsforUrl($fields, $isConfigDemoMode = false)
    {
        $url = $this->getFormUrl(null, $isConfigDemoMode);

        // Issue some empty values will not be presenting in the url causing signature issues
//        if (count($fields)) {
//            $url = $url . '?' . http_build_query($fields, '', '&');
//        }

        $count = 0;
        $size = count($fields);
        foreach ($fields as $field => $value) {
            if ($count == 0) {
                $url .= "?";
            }
            $url .= urlencode($field) . "=" . urlencode($value);
            if ($count != $size) {
                $url .= "&";
            }
            ++$count;
        }

        return $url;
    }

    /**
     * @desc prepares an array with order detail values to call the Adyen HPP page.
     *
     * @param $orderCurrencyCode
     * @param $realOrderId
     * @param $orderGrandTotal
     * @param $shopperEmail
     * @param $customerId
     * @param $merchantReturnData
     * @param $orderStoreId
     * @param $storeLocaleCode
     * @param $billingCountryCode
     * @param $shopperIP
     * @param $infoInstanceCCType
     * @param $infoInstanceMethod
     * @param $issuerId
     * @param $paymentMethodCode
     * @param $hasDeliveryAddress
     * @param $extraData
     * @param $order
     *
     * @return array
     */
    public function prepareFields(
        $orderCurrencyCode,
        $incrementId,
        $realOrderId,
        $orderGrandTotal,
        $shopperEmail,
        $customerId,
        $merchantReturnData,
        $orderStoreId,
        $storeLocaleCode,
        $billingCountryCode,
        $shopperIP,
        $infoInstanceCCType,
        $infoInstanceMethod,
        $issuerId,
        $paymentMethodCode,
        $hasDeliveryAddress,
        $order
    )
    {
        // check if Pay By Mail has a skincode, otherwise use HPP
        $skinCode = trim($this->getConfigData('skin_code', $paymentMethodCode, $orderStoreId));
        if ($skinCode == "") {
            $skinCode = trim($this->getConfigData('skinCode', 'adyen_hpp', $orderStoreId));
        }

        $merchantAccount = trim($this->getConfigData('merchantAccount', null, $orderStoreId));
        $amount = $this->formatAmount($orderGrandTotal, $orderCurrencyCode);

        $shopperLocale = trim($this->getConfigData('shopperlocale', null, $orderStoreId));
        $shopperLocale = (!empty($shopperLocale)) ? $shopperLocale : $storeLocaleCode;

        $countryCode = trim($this->getConfigData('countryCode', null, $orderStoreId));
        $countryCode = (!empty($countryCode)) ? $countryCode : $billingCountryCode;

        // shipBeforeDate is a required field by certain payment methods
        $deliveryDays = (int)$this->getConfigData('delivery_days', 'adyen_hpp', $orderStoreId);
        $deliveryDays = (!empty($deliveryDays)) ? $deliveryDays : 5;

        $shipBeforeDate = new DateTime("now");
        $shipBeforeDate->add(new DateInterval("P{$deliveryDays}D"));

        // number of days link is valid to use
        $sessionValidity = (int)trim($this->getConfigData('session_validity', 'adyen_pay_by_mail', $orderStoreId));
        $sessionValidity = ($sessionValidity == "") ? 3 : $sessionValidity;

        $sessionValidityDate = new DateTime("now");
        $sessionValidityDate->add(new DateInterval("P{$sessionValidity}D"));

        // is recurring?
        $recurringType = trim($this->getConfigData('recurringtypes', 'adyen_abstract', $orderStoreId));

        // @todo Paypal does not allow ONECLICK,RECURRING will be fixed on adyen platform but this is the quickfix for now
        if ($infoInstanceMethod == "adyen_hpp_paypal" && $recurringType == 'ONECLICK,RECURRING') {
            $recurringType = "RECURRING";
        }

        $customerId = $this->getShopperReference($customerId, $realOrderId);

        // should billing and shipping address and customer info be shown, hidden or editable on the HPP page.
        // this is heavily influenced by payment method requirements and best be left alone
        $viewDetails = $this->getHppViewDetails($infoInstanceCCType, $paymentMethodCode, $hasDeliveryAddress);
        $billingAddressType = $viewDetails['billing_address_type'];
        $deliveryAddressType = $viewDetails['shipping_address_type'];
        $shopperType = $viewDetails['customer_info'];

        // set Shopper, Billing and DeliveryAddress
        $shopperInfo = $this->getHppShopperDetails($order->getBillingAddress(), $order->getCustomerGender(), $order->getCustomerDob());
        $billingAddress = $this->getHppBillingAddressDetails($order->getBillingAddress());
        $deliveryAddress = $this->getHppDeliveryAddressDetails($order->getShippingAddress());
        $openInvoiceData = $this->getOpenInvoiceData($incrementId, $order);


        // if option to put Return Url in request from magento is enabled add this in the request
        if ($paymentMethodCode != Adyen_Payment_Model_Adyen_PayByMail::METHODCODE) {
            $returnUrlInRequest = $this->getConfigData('return_url_in_request', 'adyen_hpp', $orderStoreId);
            $returnUrl = ($returnUrlInRequest) ?
                trim(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true) . "adyen/process/success") :
                "";
        } else {
            $returnUrl = null;
        }

        // type of payment method (card)
        $brandCode = $paymentMethodCode == Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE ?
            trim($this->getConfigData('openinvoicetypes', Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE, $orderStoreId)) :
            trim($infoInstanceCCType);

        // Risk offset, 0 to 100 points
        $adyFields['offset'] = "0";

        $browserInfo = trim($_SERVER['HTTP_USER_AGENT']);

        /*
         * This field will be appended as-is to the return URL when the shopper completes, or abandons, the payment and
         * returns to your shop; it is typically used to transmit a session ID. This field has a maximum of 128 characters
         * This is an optional field and not necessary by default
         */
        $dataString = (is_array($merchantReturnData)) ? serialize($merchantReturnData) : $merchantReturnData;


        $dfValue = null;
        if ($order->getPayment()->getAdditionalInformation('dfvalue')) {
            $dfValue = $order->getPayment()->getAdditionalInformation('dfvalue');
        }

        $adyFields = $this->adyenValueArray(
            $orderCurrencyCode,
            $shopperEmail,
            $customerId,
            $merchantAccount,
            $incrementId,
            $amount,
            $shipBeforeDate,
            $skinCode,
            $shopperLocale,
            $countryCode,
            $recurringType,
            $dataString,
            $browserInfo,
            $shopperIP,
            $billingAddressType,
            $deliveryAddressType,
            $shopperType,
            $issuerId,
            $returnUrl,
            $brandCode,
            $shopperInfo,
            $billingAddress,
            $deliveryAddress,
            $openInvoiceData,
            $dfValue
        );

        // eventHandler to overwrite the adyFields without changing module code
        $adyFields = new Varien_Object($adyFields);
        Mage::dispatchEvent('adyen_payment_prepare_fields', array(
            'fields' => $adyFields
        ));

        // @deprecated in favor of above event, this one is left in for backwards compatibility
        Mage::dispatchEvent('adyen_payment_hpp_fields', array(
            'order' => $order,
            'fields' => $adyFields
        ));
        $adyFields = $adyFields->getData();

        return $adyFields;
    }

    /**
     * Format the data in a specific array
     *
     * @param $orderCurrencyCode
     * @param $shopperEmail
     * @param $customerId
     * @param $merchantAccount
     * @param $merchantReference
     * @param $amount
     * @param $shipBeforeDate
     * @param $skinCode
     * @param $shopperLocale
     * @param $countryCode
     * @param $recurringType
     * @param $dataString
     * @param $browserInfo
     * @param $shopperIP
     * @param $billingAddressType
     * @param $deliveryAddressType
     * @param $shopperType
     * @param $issuerId
     * @param $returnUrl
     * @param $brandCode
     * @param $shopperInfo
     * @param $billingAddress
     * @param $deliveryAddress
     * @param $openInvoiceData
     *
     * @return array
     */
    public function adyenValueArray(
        $orderCurrencyCode,
        $shopperEmail,
        $customerId,
        $merchantAccount,
        $merchantReference,
        $amount,
        $shipBeforeDate,
        $skinCode,
        $shopperLocale,
        $countryCode,
        $recurringType,
        $dataString,
        $browserInfo,
        $shopperIP,
        $billingAddressType,
        $deliveryAddressType,
        $shopperType,
        $issuerId,
        $returnUrl,
        $brandCode,
        $shopperInfo,
        $billingAddress,
        $deliveryAddress,
        $openInvoiceData,
        $dfValue = null
    )
    {
        $adyFields = array(
            'merchantAccount' => $merchantAccount,
            'merchantReference' => $merchantReference,
            'paymentAmount' => (int)$amount,
            'currencyCode' => $orderCurrencyCode,
            'shipBeforeDate' => $shipBeforeDate->format('Y-m-d'),
            'skinCode' => $skinCode,
            'shopperLocale' => $shopperLocale,
            'countryCode' => $countryCode,
            'sessionValidity' => $shipBeforeDate->format("c"),
            'shopperEmail' => $shopperEmail,
            'recurringContract' => $recurringType,
            'shopperReference' => $customerId,
            'shopperIP' => $shopperIP,
            'browserInfo' => $browserInfo,
            'resURL' => $returnUrl,
            'merchantReturnData' => substr(urlencode($dataString), 0, 128),
            // @todo remove this and add allowed methods via a config xml node
            'blockedMethods' => "",
            // Will only work if billingAddress, deliveryAddress and shopperInfo is in request
            'billingAddressType' => $billingAddressType,
            'deliveryAddressType' => $deliveryAddressType,
            'shopperType' => $shopperType
        );

        if (!empty($issuerId)) {
            $adyFields["issuerId"] = $issuerId;
        }

        // explode details for request
        $adyFields = $this->explodeArrayToRequestFields($adyFields, 'shopper', $shopperInfo);
        $adyFields = $this->explodeArrayToRequestFields($adyFields, 'billingAddress', $billingAddress);
        $adyFields = $this->explodeArrayToRequestFields($adyFields, 'deliveryAddress', $deliveryAddress);

        // merge openInvoiceData
        $adyFields = $adyFields + $openInvoiceData;

        // Add brandCode if payment selection is done
        if ($brandCode) {
            $adyFields['brandCode'] = $brandCode;
        }

        if ($dfValue) {
            $adyFields['dfValue'] = $dfValue;
        }

        return $adyFields;
    }

    public function explodeArrayToRequestFields($adyFields, $name, $items)
    {
        if (is_array($items)) {
            foreach ($items as $field => $value) {
                $adyFields[$name . '.' . $field] = $value;
            }
        }
        return $adyFields;
    }

    /**
     * @param null $storeId
     * @param $paymentMethodCode
     * @return string
     */
    public function _getSecretWord($storeId = null, $paymentMethodCode)
    {
        $skinCode = trim($this->getConfigData('skin_code', $paymentMethodCode, $storeId));
        if ($skinCode == "") { // fallback if no skincode is available for the specific method
            $paymentMethodCode = 'adyen_hpp';
        }

        switch ($this->getConfigDataDemoMode()) {
            case true:
                $secretWord = trim($this->getConfigData('secret_wordt', $paymentMethodCode, $storeId));
                break;
            default:
                $secretWord = trim($this->getConfigData('secret_wordp', $paymentMethodCode, $storeId));
                break;
        }
        return $secretWord;
    }

    /**
     * @desc The character escape function is called from the array_map function in _signRequestParams
     * @param $val
     * @return string
     */
    public function escapeString($val)
    {
        return str_replace(':', '\\:', str_replace('\\', '\\\\', $val));
    }

    /**
     * @descr Hmac key signing is standardised by Adyen
     * - first we order the array by string
     * - then we create a column seperated array with first all the keys, then all the values
     * - finally generating the SHA256 HMAC encrypted merchant signature
     * @param $adyFields
     * @param $secretWord
     * @return string
     */
    public function createHmacSignature($adyFields, $secretWord)
    {
        ksort($adyFields, SORT_STRING);

        $signData = implode(":", array_map(array($this, 'escapeString'), array_merge(
            array_keys($adyFields),
            array_values($adyFields)
        )));

        $signMac = Zend_Crypt_Hmac::compute(pack("H*", $secretWord), 'sha256', $signData);

        return base64_encode(pack('H*', $signMac));
    }

    /**
     * @param $customerId
     * @param $realOrderId
     * @return string
     */
    public function getShopperReference($customerId, $realOrderId)
    {
        if ($customerId) { // there is a logged in customer for this order
            // the following allows to send the 'pretty' customer ID or increment ID to Adyen instead of the entity id
            // used collection here, it's about half the resources of using the load method on the customer opject
            /* var $customer Mage_Customer_Model_Resource_Customer_Collection */
            $collection = Mage::getResourceModel('customer/customer_collection')
                ->addAttributeToSelect('adyen_customer_ref')
                ->addAttributeToSelect('increment_id')
                ->addAttributeToFilter('entity_id', $customerId);
            $collection->getSelect()->limit(1);
            $customer = $collection->getFirstItem();

            if ($customer->getData('adyen_customer_ref')) {
                $customerId = $customer->getData('adyen_customer_ref');
            } elseif ($customer->getData('increment_id')) {
                $customerId = $customer->getData('increment_id');
            } else {
                $customerId = $customer->getId();
            }

            return $customerId;
        } else { // it was a guest order
            $customerId = self::GUEST_ID . $realOrderId;
            return $customerId;
        }
    }

    /**
     * @param $infoInstanceCCType
     * @param $paymentMethodCode
     * @param $hasDeliveryAddress
     * @return array
     */
    public function getHppViewDetails($infoInstanceCCType, $paymentMethodCode, $hasDeliveryAddress)
    {
        // should the HPP page show address and delivery type details
        if ($paymentMethodCode == Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE || Mage::helper('adyen')->isOpenInvoice($infoInstanceCCType)) {
            $billingAddressType = "1"; // yes, but not editable
            $deliveryAddressType = "1"; // yes, but not editable

            // get shopperType setting
            $shopperType = $this->getConfigData("shoppertype", Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE) == '1' ? "" : "1"; // only for openinvoice show this
        } else {
            $shopperType = "";
            // for other payment methods like creditcard don't show the address field on the HPP page
            $billingAddressType = "2";
            // Only show DeliveryAddressType to hidden in request if there is a shipping address otherwise keep it empty
            $deliveryAddressType = $hasDeliveryAddress ? "2" : "";
        }

        return array(
            'billing_address_type' => $billingAddressType,
            'shipping_address_type' => $deliveryAddressType,
            'customer_info' => $shopperType
        );
    }


    /**
     * @param $billingAddress
     * @param $gender
     * @param $dob
     * @return array
     */
    public function getHppShopperDetails($billingAddress, $gender, $dob)
    {
        $middleName = trim($billingAddress->getMiddlename());

        $shopperInfo = array();
        $shopperInfo['firstName'] = trim($billingAddress->getFirstname());
        $shopperInfo['infix'] = $middleName != "" ? trim($middleName) : "";
        $shopperInfo['lastName'] = trim($billingAddress->getLastname());
        $shopperInfo['gender'] = $this->getGenderText($gender);

        if (!empty($dob)) {
            $shopperInfo['dateOfBirthDayOfMonth'] = trim($this->getDate($dob, 'd'));
            $shopperInfo['dateOfBirthMonth'] = trim($this->getDate($dob, 'm'));
            $shopperInfo['dateOfBirthYear'] = trim($this->getDate($dob, 'Y'));
        }

        $shopperInfo['telephoneNumber'] = trim($billingAddress->getTelephone());

        return $shopperInfo;
    }

    /**
     * Date Manipulation
     *
     * @param null $date
     * @param string $format
     * @return string
     */
    public function getDate($date = null, $format = 'Y-m-d H:i:s')
    {
        if (strlen($date) < 0) {
            $date = date('d-m-Y H:i:s');
        }
        $timeStamp = new DateTime($date);
        return $timeStamp->format($format);
    }

    /**
     * @param $genderId
     * @return string
     */
    public function getGenderText($genderId)
    {
        $result = "";
        if ($genderId == '1') {
            $result = 'MALE';
        } elseif ($genderId == '2') {
            $result = 'FEMALE';
        }
        return $result;
    }

    /**
     * @param $billingAddress
     * @return array
     */
    public function getHppBillingAddressDetails($billingAddress)
    {
        $billingAddressRequest = array(
            'street' => 'N/A',
            'houseNumberOrName' => 'N/A',
            'city' => 'N/A',
            'postalCode' => 'N/A',
            'stateOrProvince' => 'N/A',
            'country' => 'N/A'
        );

        if (trim($this->getStreet($billingAddress, true)->getName()) != "") {
            $billingAddressRequest['street'] = trim($this->getStreet($billingAddress, true)->getName());
        }

        if ($this->getStreet($billingAddress, true)->getHouseNumber() != "") {
            $billingAddressRequest['houseNumberOrName'] = trim($this->getStreet($billingAddress, true)->getHouseNumber());
        }

        if (trim($billingAddress->getCity()) != "") {
            $billingAddressRequest['city'] = trim($billingAddress->getCity());
        }

        if (trim($billingAddress->getPostcode()) != "") {
            $billingAddressRequest['postalCode'] = trim($billingAddress->getPostcode());
        }

        if (trim($billingAddress->getRegionCode()) != "") {
            $region = is_numeric($billingAddress->getRegionCode())
                ? $billingAddress->getRegion()
                : $billingAddress->getRegionCode();

            $billingAddressRequest['stateOrProvince'] = trim($region);
        }

        if (trim($billingAddress->getCountryId()) != "") {
            $billingAddressRequest['country'] = trim($billingAddress->getCountryId());
        }

        return $billingAddressRequest;
    }


    /**
     * @param $deliveryAddress
     * @return array
     */
    public function getHppDeliveryAddressDetails($deliveryAddress)
    {
        // Gift Cards and downloadable products don't have delivery addresses
        if (!is_object($deliveryAddress)) {
            return null;
        }

        $deliveryAddressRequest = array(
            'street' => 'N/A',
            'houseNumberOrName' => 'N/A',
            'city' => 'N/A',
            'postalCode' => 'N/A',
            'stateOrProvince' => 'N/A',
            'country' => 'N/A'
        );

        if (trim($this->getStreet($deliveryAddress, true)->getName() != "")) {
            $deliveryAddressRequest['street'] = trim($this->getStreet($deliveryAddress, true)->getName());
        }

        if (trim($this->getStreet($deliveryAddress, true)->getHouseNumber()) != "") {
            $deliveryAddressRequest['houseNumberOrName'] = trim($this->getStreet($deliveryAddress, true)->getHouseNumber());
        }

        if (trim($deliveryAddress->getCity()) != "") {
            $deliveryAddressRequest['city'] = trim($deliveryAddress->getCity());
        }

        if (trim($deliveryAddress->getPostcode()) != "") {
            $deliveryAddressRequest['postalCode'] = trim($deliveryAddress->getPostcode());
        }

        if (trim($deliveryAddress->getRegionCode()) != "") {
            $deliveryAddressRequest['stateOrProvince'] = trim($deliveryAddress->getRegionCode());
        }

        if (trim($deliveryAddress->getCountryId()) != "") {
            $deliveryAddressRequest['country'] = trim($deliveryAddress->getCountryId());
        }

        return $deliveryAddressRequest;
    }

    /**
     * Get openinvoice data lines
     *
     * @param $merchantReference
     * @param $order
     * @return array
     */
    public function getOpenInvoiceData($merchantReference, $order)
    {
        $count = 0;
        $currency = $order->getOrderCurrencyCode();
        $openInvoiceData = array();

        // loop through items
        foreach ($order->getItemsCollection() as $item) {

            //skip dummies
            if ($item->isDummy()) continue;

            ++$count;

            $linename = "line" . $count;
            $openInvoiceData['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $openInvoiceData['openinvoicedata.' . $linename . '.description'] = str_replace("\n", '', trim($item->getName()));
            $openInvoiceData['openinvoicedata.' . $linename . '.itemAmount'] = $this->formatAmount($item->getPrice(), $currency);
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatAmount'] = ($item->getTaxAmount() > 0 && $item->getPriceInclTax() > 0) ? $this->formatAmount($item->getPriceInclTax(), $currency) - $this->formatAmount($item->getPrice(), $currency) : $this->formatAmount($item->getTaxAmount(), $currency);
            // Calculate vat percentage
            $id = $item->getProductId();
            $product = $this->loadProductById($id);
            $taxRate = $this->getTaxRate($order, $product->getTaxClassId());
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatPercentage'] = $this->getMinorUnitTaxPercent($taxRate);
            $openInvoiceData['openinvoicedata.' . $linename . '.numberOfItems'] = (int)$item->getQtyOrdered();


            if ($this->isHighVatCategory($order->getPayment())) {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }

            // Needed for RatePay
            if ($item->getSku() != "") {
                $openInvoiceData['openinvoicedata.' . $linename . '.itemId'] = $item->getSku();
            }
        }
        //discount cost
        if ($order->getDiscountAmount() > 0 || $order->getDiscountAmount() < 0) {
            $linename = "line" . ++$count;
            $openInvoiceData['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $openInvoiceData['openinvoicedata.' . $linename . '.description'] = $this->__('Total Discount');
            $openInvoiceData['openinvoicedata.' . $linename . '.itemAmount'] = $this->formatAmount($order->getDiscountAmount(), $currency);
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatAmount'] = "0";
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatPercentage'] = "0";
            $openInvoiceData['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
            if ($this->isHighVatCategory($order->getPayment())) {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }
        }
        //shipping cost
        if ($order->getShippingAmount() > 0 || $order->getShippingTaxAmount() > 0) {
            $linename = "line" . ++$count;
            $openInvoiceData['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $openInvoiceData['openinvoicedata.' . $linename . '.description'] = $order->getShippingDescription();
            $openInvoiceData['openinvoicedata.' . $linename . '.itemAmount'] = $this->formatAmount($order->getShippingAmount(), $currency);
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatAmount'] = $this->formatAmount($order->getShippingTaxAmount(), $currency);
            // Calculate vat percentage
            $taxClass = Mage::getStoreConfig('tax/classes/shipping_tax_class', $order->getStoreId());
            $taxRate = $this->getTaxRate($order, $taxClass);
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatPercentage'] = $this->getMinorUnitTaxPercent($taxRate);
            $openInvoiceData['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
            if ($this->isHighVatCategory($order->getPayment())) {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }
        }
        if ($order->getPaymentFeeAmount() > 0) {
            $linename = "line" . ++$count;
            $openInvoiceData['openinvoicedata.' . $linename . '.currencyCode'] = $currency;
            $openInvoiceData['openinvoicedata.' . $linename . '.description'] = $this->__('Payment Fee');
            $openInvoiceData['openinvoicedata.' . $linename . '.itemAmount'] = $this->formatAmount($order->getPaymentFeeAmount(), $currency);
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatAmount'] = "0";
            $openInvoiceData['openinvoicedata.' . $linename . '.itemVatPercentage'] = "0";
            $openInvoiceData['openinvoicedata.' . $linename . '.numberOfItems'] = 1;
            if ($this->isHighVatCategory($order->getPayment())) {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "High";
            } else {
                $openInvoiceData['openinvoicedata.' . $linename . '.vatCategory'] = "None";
            }
        }

        $openInvoiceData['openinvoicedata.refundDescription'] = "Refund / Correction for " . $merchantReference;
        $openInvoiceData['openinvoicedata.numberOfLines'] = $count;

        return $openInvoiceData;
    }

    /**
     * Checks if HigVat Cateogry is needed
     *
     * @param $paymentMethod
     * @return bool
     */
    public function isHighVatCategory($paymentMethod)
    {
        if ($this->isOpenInvoiceMethod($paymentMethod->getMethod()) || Mage::helper('adyen')->isAfterPay($paymentMethod->getMethodInstance()->getInfoInstance()->getCcType())) {
            return true;
        }
        return false;
    }

    /**
     * Check if the payment method is openinvoice and the payment method type is afterpay_default
     *
     * @param $method
     * @return bool
     */
    public function isOpenInvoiceMethod($method)
    {
        $openinvoiceType = $this->getConfigData('openinvoicetypes', Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE);

        if ($method == Adyen_Payment_Model_Adyen_Openinvoice::METHODCODE && $openinvoiceType == self::AFTERPAY_DEFAULT) {
            return true;
        }

        return false;
    }

    public function loadProductById($id)
    {
        return Mage::getModel('catalog/product')->load($id);
    }
}
