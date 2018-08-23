<?php

class Martin_Flytcloud_Model_System_Config_Backend_Password extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        $value = $this->getValue();
        if(!$this->is_md5($value))
        {
            $value=MD5($value);
        }
        $this->setValue($value);
        return $this;
    }
    function is_md5($password) {
       return preg_match("/^[a-z0-9]{32}$/", $password);
    }
}
