<?php
class Martin_SalesReports_Helper_Data extends Mage_Core_Helper_Abstract{
    protected $_locale;
    public function getParam($key){
        $session = Mage::getSingleton('adminhtml/session');
        $sessionParamName = 'salesreports-'.$key;
        $value = null;
        if($value = Mage::app()->getRequest()->getParam($key)){
            $session->setData($sessionParamName, $value);
        }else{
            $value = $session->getData($sessionParamName);
        }
        return $value;
    }

    public function convertDate($date, $locale)
    {
        try {
            $dateObj = $this->getLocale()->date(null, null, $locale, false);

            //set default timezone for store (admin)
            $dateObj->setTimezone(
                Mage::app()->getStore()->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE)
            );

            //set begining of day
            $dateObj->setHour(00);
            $dateObj->setMinute(00);
            $dateObj->setSecond(00);

            //set date with applying timezone of store
            $dateObj->set($date, Zend_Date::DATE_SHORT, $locale);

            //convert store date to default date in UTC timezone without DST
            $dateObj->setTimezone(Mage_Core_Model_Locale::DEFAULT_TIMEZONE);

            //$time =$dateObj->toString('Y-MM-dd HH:mm:ss');
            //return $time;
            return $dateObj;
        }
        catch (Exception $e) {
            return null;
        }
    }

    public function getLocale()
    {
        if (!$this->_locale) {
            $this->_locale = Mage::app()->getLocale();
        }
        return $this->_locale;
    }
}