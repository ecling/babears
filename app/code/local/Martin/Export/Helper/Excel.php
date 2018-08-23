<?php

class Martin_Export_Helper_Excel extends Mage_Core_Helper_Abstract
{
    protected $_isExcelPluginIncluded=false;

    public function __construct()
    {
        $this->_includeExcelPlugin();
    }
    public function _includeExcelPlugin()
    {
        if(include_once 'PHPExcel'.DS.'PHPExcel.php') $this->_isExcelPluginIncluded=true;
    }
    public function getExcel()
    {
        return new PHPExcel();
    }
    public function isExcelPluginIncluded()
    {
        return $this->_isExcelPluginIncluded;
    }
}