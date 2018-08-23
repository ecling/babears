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
class Adyen_Payment_Model_Authenticate extends Mage_Core_Model_Abstract
{

    /**
     * @param type $actionName
     * @param type $varienObj
     * @return type
     */
    public function authenticate($actionName, $varienObj)
    {
        switch ($actionName) {
            case 'success':
                $authStatus = $this->_signAuthenticate($varienObj);
                break;
            default:
                $authStatus = $this->_httpAuthenticate($varienObj);
                break;
        }
        return $authStatus;
    }

    /**
     * @desc Authenticate using sha1 Merchant signature
     * @see success Action during checkout
     * @param Varien_Object $response
     */
    protected function _signAuthenticate(Varien_Object $response)
    {
        if ($this->_getConfigData('demoMode') === 'Y') {
            $secretWord = $this->_getConfigData('secret_wordt', 'adyen_hpp');
        } else {
            $secretWord = $this->_getConfigData('secret_wordp', 'adyen_hpp');
        }

        // do it like this because $_GET is converting dot to underscore
        $queryString = $_SERVER['QUERY_STRING'];
        $result = array();
        $pairs = explode("&", $queryString);

        foreach ($pairs as $pair) {
            $nv = explode("=", $pair);
            $name = urldecode($nv[0]);
            $value = urldecode($nv[1]);
            $result[$name] = $value;
        }

        // do not use merchantSig in calculation
        unset($result['merchantSig']);

        // Sort the array by key using SORT_STRING order
        ksort($result, SORT_STRING);

        $signData = implode(":", array_map(array($this, 'escapeString'), array_merge(array_keys($result), array_values($result))));

        $signMac = Zend_Crypt_Hmac::compute(pack("H*", $secretWord), 'sha256', $signData);
        $localStringToHash = base64_encode(pack('H*', $signMac));

        if (strcmp($localStringToHash, $response->getData('merchantSig')) === 0) {
            return true;
        }
        return false;
    }

    /*
   * @desc The character escape function is called from the array_map function in _signRequestParams
   * $param $val
   * return string
   */
    protected function escapeString($val)
    {
        return str_replace(':', '\\:', str_replace('\\', '\\\\', $val));
    }

    /**
     * Authenticate using http_auth
     *
     * @param Varien_Object $response
     * @return array
     */
    protected function _httpAuthenticate(Varien_Object $response)
    {
        $result = array(
            'authentication' => false, 'message' => ''
        );

        $this->fixCgiHttpAuthentication(); //add cgi support
        $internalMerchantAccount = $this->_getConfigData('merchantAccount');
        $username = $this->_getConfigData('notification_username');
        $password = Mage::helper('core')->decrypt($this->_getConfigData('notification_password'));
        $submitedMerchantAccount = $response->getData('merchantAccountCode');

        if (empty($submitedMerchantAccount) && empty($internalMerchantAccount)) {
            if (strtolower(substr($response->getData('pspReference'), 0, 17)) == "testnotification_" || strtolower(substr($response->getData('pspReference'), 0, 5)) == "test_") {
                Mage::log('Notification test failed: merchantAccountCode is empty in magento settings', Zend_Log::DEBUG, "adyen_notification.log", true);
                $result['message'] = 'merchantAccountCode is empty in magento settings';
            }
            return $result;
        }

        // validate username and password
        if ((!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW']))) {
            if (strtolower(substr($response->getData('pspReference'), 0, 17)) == "testnotification_" || strtolower(substr($response->getData('pspReference'), 0, 5)) == "test_") {
                Mage::log('Authentication failed: PHP_AUTH_USER and PHP_AUTH_PW are empty. See Adyen Magento manual CGI mode', Zend_Log::DEBUG, "adyen_notification.log", true);
                $result['message'] = 'Authentication failed: PHP_AUTH_USER and PHP_AUTH_PW are empty. See Adyen Magento manual CGI mode';
            }
            return $result;
        }

        $accountCmp = !$this->_getConfigData('multiple_merchants')
            ? strcmp($submitedMerchantAccount, $internalMerchantAccount)
            : 0;
        $usernameCmp = strcmp($_SERVER['PHP_AUTH_USER'], $username);
        $passwordCmp = strcmp($_SERVER['PHP_AUTH_PW'], $password);
        if ($accountCmp === 0 && $usernameCmp === 0 && $passwordCmp === 0) {
            $result['authentication'] = true;
            return $result;
        }

        // If notification is test check if fields are correct if not return error
        if (strtolower(substr($response->getData('pspReference'), 0, 17)) == "testnotification_" || strtolower(substr($response->getData('pspReference'), 0, 5)) == "test_") {
            if ($accountCmp != 0) {
                Mage::log('MerchantAccount in notification is not the same as in Magento settings', Zend_Log::DEBUG, "adyen_notification.log", true);
                $result['message'] =  'MerchantAccount in notification is not the same as in Magento settings';
            } elseif ($usernameCmp != 0 || $passwordCmp != 0) {
                Mage::log('username (PHP_AUTH_USER) and\or password (PHP_AUTH_PW) are not the same as Magento settings', Zend_Log::DEBUG, "adyen_notification.log", true);
                $result['message'] = 'username (PHP_AUTH_USER) and\or password (PHP_AUTH_PW) are not the same as Magento settings';
            }
        }

        return $result;
    }

    /**
     * Fix these global variables for the CGI if needed
     */
    public function fixCgiHttpAuthentication()
    { // unsupported is $_SERVER['REMOfixCgiHttpAuthenticationTE_AUTHORIZATION']: as stated in manual :p

        // do nothing if values are already there
        if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
            return;
        } else if (isset($_SERVER['REDIRECT_REMOTE_AUTHORIZATION']) && $_SERVER['REDIRECT_REMOTE_AUTHORIZATION'] != '') { //pcd note: no idea who sets this
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode($_SERVER['REDIRECT_REMOTE_AUTHORIZATION']), 2);
        } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) { //pcd note: standard in magento?
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)), 2);
        } elseif (!empty($_SERVER['REMOTE_USER'])) { //pcd note: when cgi and .htaccess modrewrite patch is executed
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['REMOTE_USER'], 6)), 2);
        } elseif (!empty($_SERVER['REDIRECT_REMOTE_USER'])) { //pcd note: no idea who sets this
            list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['REDIRECT_REMOTE_USER'], 6)), 2);
        }
    }

    /**
     * @desc Give Default settings
     * @example $this->_getConfigData('demoMode','adyen_abstract')
     * @since 0.0.2
     * @param string $code
     */
    protected function _getConfigData($code, $paymentMethodCode = null, $storeId = null)
    {
        return Mage::helper('adyen')->_getConfigData($code, $paymentMethodCode, $storeId);
    }

}
