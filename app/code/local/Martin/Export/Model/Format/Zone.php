<?php

/**
 *  @method   getStartCell()
 *  @method   setStartCell($cell)
 * 
 * 
 * @method   setRowsNum($num)
 * $method  int getRowsNum()
 * 
 * $method  setCurrentCell()
 * $method  getCurrentCell($cell)
 * 
 * @method setTitles($titles);
 * @method getTitles();
 */
class Martin_Export_Model_Format_Zone extends Varien_Object
{
    protected $_startCell;
    protected $_colsNum;
    protected $_rowsNum;
    protected $_focusOn;
    
    public function focusOn($cell){
        $this->_focusOn=$cell;
        return $this;
    }
    public function getfocusCell()
    {
        return $this->_focusOn;
    }
    
    public function getColsNum()
    {
        $titles=$this->getTitles();
        if($titles){
            return count($titles);
        }else{
            throw new Exception("couldn't calculate cols num of zone,please set titles first");
        }
    }
    public function getColByTitle($title)
    {
        $startCell=$this->getStartCell();
        if($startCell)
        {
            $titels=$this->getTitles();
            if($titels)
            {
                $index= array_search($title, $titels);
                if($index!==false){
                    list($col,$row)=Mage::helper('martinexport/excel_cell')->separateColRow($startCell);
                    return chr(ord($col)+$index);
                }
            }else{
                throw new Exception("couldn't get col by title , please set titles cell first");
            }
        }else{
            throw new Exception("couldn't get col by title , please set start cell first");
        }
    }

}