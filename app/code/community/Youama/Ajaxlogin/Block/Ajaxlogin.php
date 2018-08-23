<?php

/**
 * YouAMA.com
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA that is bundled with this package
 * on http://youama.com/freemodule-license.txt.
 *
 *******************************************************************************
 *                          MAGENTO EDITION USAGE NOTICE
 *******************************************************************************
 * This package designed for Magento Community edition. Developer(s) of
 * YouAMA.com does not guarantee correct work of this extension on any other
 * Magento edition except Magento Community edition. YouAMA.com does not
 * provide extension support in case of incorrect edition usage.
 *******************************************************************************
 *                                  DISCLAIMER
 *******************************************************************************
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future.
 *******************************************************************************
 * @category   Youama
 * @package    Youama_Ajaxlogin
 * @copyright  Copyright (c) 2012-2014 YouAMA.com (http://www.youama.com)
 * @license    http://youama.com/freemodule-license.txt
 */

/**
 * Block class for ajaxlogin view.
 * Class Youama_Ajaxlogin_Block_Ajaxlogin
 * @author doveid
 */
class Youama_Ajaxlogin_Block_Ajaxlogin extends Mage_Core_Block_Template
{
    /**
     * Retrieve string 1 if Redirection to profile is YES on system config page.
     * @return string
     */

    const XML_PATH_IS_SSL = 'web/secure/use_in_frontend';

    private static $curr_url;
    private static $_isSecureEnabled;
    private static $_secureFlag;
    private static $url;

    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _getHelper($path)
    {
        return Mage::helper($path);
    }

    public function isRedirectToProfile()
    {
        if (Mage::getStoreConfig('youamaajaxlogin/settings/redirection')) {
            return '1';
        }

        return '0';
    }

    public function substituteUrl()
    {   

        $url = $this->currentUrl();
        $pos = strpos($url, 'http:');

        if(($this->isSecureEnabled() == 1) && ($pos !== false)){
            $_secureFlag = false;
        } else{
            $_secureFlag = true;
        }

        $curr_url = $this->getUrl("youama_ajaxlogin/ajax/index", array("_secure" => $_secureFlag));

        return $curr_url;
    }

    protected function currentUrl()
    {

        return Mage::helper('core/url')->getCurrentUrl();

    }

    protected function isSecureEnabled()
    {

        if (self::$_isSecureEnabled === null) {
            $storeId = $this->getStoreId() ? $this->getStoreId() : null;
            self::$_isSecureEnabled = (bool)Mage::getStoreConfig(self::XML_PATH_IS_SSL, $storeId);
        }

        return self::$_isSecureEnabled;
    }

}