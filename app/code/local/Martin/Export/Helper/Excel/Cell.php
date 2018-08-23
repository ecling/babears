<?php

class Martin_Export_Helper_Excel_Cell extends Mage_Core_Helper_Abstract{
    public function getCol($cell){
        return $this->separateColRow($cell,'col');
    }
    public function getRow($cell)
    {
        return $this->separateColRow($cell,'row');
    }
    public function separateColRow($cell,$return='both')
    {
        $this->_checkCell($cell);
        $col=preg_replace('/\d/','',$cell);
        $row=preg_replace('/\D/','',$cell);
        switch($return)
        {
            case  'row':
                return $row;
                break;
            case  'col':
                return $col;
                break;
            default:
                return array($col,$row);
        }
    }
    public function _checkCell($cell)
    {
        if(!preg_match('/^[A-Z]+\d+$/',$cell))throw new Exception("ilegal cell string :$cell");
        if(ord(preg_replace('/\d/','',$cell))<ord('A'))throw new Exception("ilegal col  :$cell");
        if(preg_replace('/\D/','',$cell)<1) throw new Exception("ilegal row  :$cell");
        if(strlen($col)>1) throw new Exception("sorry, col $col is not supported now ");
    }
}
