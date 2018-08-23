<?php

class Martin_Export_Helper_Date extends Mage_Core_Helper_Abstract
{
    public function validatePassword($password)
    {
        return (string)$password===(string)$this->getFilterPassword()?true:false;
    }
    public function getFilterPassword()
    {
        $pw= Mage::app()->getConfig()->getNode('global/martinexport/filter/validate/password');
        return $pw;
    }

}
