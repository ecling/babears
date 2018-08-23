<?php

class Martin_Export_Helper_Order_Format extends Mage_Core_Helper_Abstract
{
    protected $_preZone;
    protected $_currentZone;
    protected $_currentCell;
    protected $_excel;
    
    protected $_oddZoneStartCol;
    protected $_zoneCols;
    
    protected $_zoneStartRow;
    
    protected $_styles;
    protected $_minZoneColsNum;
    
    public function setMinZoneColsNum($num)
    {
        $this->_minZoneColsNum=$num;
    }


    const STYLE_FONT_SIZE='font-size';


    public function setStyleVal($key,$value)
    {
        $this->_styles[$key]=$value;
    }
    public function getStyleVal($key)
    {
        if(isset($this->_styles[$key])){
            return $this->_styles[$key];
        }
        return null;
    }

    public function __construct() {
       $this->_oddZoneStartCol='A';
       $this->_zoneStartRow=5;
    }
    public function setCurrentZone(Martin_Export_Model_Format_Zone $productItemZone,$isEven)
    {
      $startCell=$this->_starCell($isEven);

      $productItemZone->setStartCell($startCell);
      $this->_replaceCurrentZone($productItemZone);
      return $this;
    }
    protected function  _starCell($isEven)
    {
        if(!$this->_currentZone){//first one
            return $this->_oddZoneStartCol.$this->_zoneStartRow;
        }
        
        $curZone=$this->_currentZone;
        $curStartCell=$curZone->getStartCell();
        if($isEven){
            $curCols=$curZone->getColsNum();
            if($this->_minZoneColsNum)
            {
                $curCols=$curCols>$this->_minZoneColsNum?$curCols:$this->_minZoneColsNum;
                
            }
            $col=  chr(ord($this->getCol($curStartCell))+$curCols+1); 
            $row=$this->getRow($curStartCell);
        }else{
            $col=$this->_oddZoneStartCol;
            $preRows=$this->_preZone?$this->_preZone->getRowsNum():0;
            $curRows=$this->_currentZone?$this->_currentZone->getRowsNum():0;
            $max=max($preRows,$curRows);
            $max=$max>8?$max:8;
            $row=$this->getRow($curStartCell)+$max+1;
            
        }
        $startCell=$col.$row;
        return $startCell;
    }


    protected function _replaceCurrentZone($zone)
    {
        $this->_preZone=$this->_currentZone;
        $this->_currentZone=$zone;
        $this->focusOn($zone->getStartCell());
    }


    public function setZoneStartRow($num)
    {
        if(is_int($num) && $num>0)
        {
            $this->_zoneStartRow=$num;
        }else{
            throw new Exception('ileggal zone start row num :$num');
        }
        
        return $this;
    }
    

    
    public function setZoneCols($colsNum)
    {
        $this->_zoneCols=$colsNum;
        return $this;
    }
    public function getZoneCols()
    {
        return $this->_zoneCols;
    }
   
    
    public function getExcel()
    {
        if(!$this->_excel)
        {
            $excelHelper=Mage::helper('martinexport/excel');
            if($excelHelper->isExcelPluginIncluded())
            {
                $this->_excel=new PHPExcel(); 
                $this->_excel->setActiveSheetIndex(0);
            }else{
                throw new Exception("there is no excel");
            }
        }
        return $this->_excel;
    }


    public function getCurrentZone()
    {
        return $this->_currentZone;
    }
    
    public function focusOn($cell)
    {
//        var_dump("--qiguai--$cell");
//        if('C67'==$cell){
//            var_dump(debug_backtrace());exit;
//        }
        $this->_currentCell=$cell;
        return $this;
    }
    public function focusOff()
    {
        $this->_currentCell=null;
    }
    public function getFocusOn()
    {
        return $this->_currentCell;
    }

    
    /*
     * $focusOn=true 移动之后聚焦，移动之后的单元格即为currentCell
     */
    public function rightMove($cell,$colsNum=1,$focusOn=true)
    {
        list($col,$row)=$this->_separateColRow($cell);
        $nextCell= chr(ord($col)+1).$row;
        if($focusOn)
        {
            $this->focusOn($nextCell);
        }
        return $this;
    }
    
    public function downMove($cell,$rowsNum=1,$focusOn=true)
    {
        list($col,$row)=$this->_separateColRow($cell);
        
        $nextCell= $col.($row+$rowsNum);
        if($focusOn)
        {
            $this->focusOn($nextCell);
        }
        return $this;
    }

    public function moveToCol($cell,$nextCol,$focusOn=true)
    {
        list($col,$row)=$this->_separateColRow($cell);
        $nextCell=$nextCol.$row;
        if($focusOn)
        {
            $this->focusOn($nextCell);
        }
        return $this;
    }
    public function setValue($cell,$value)
    {
        $activeSheet=$this->_getActiveSheet();
        $activeSheet->getCell($cell)->setValue( $value);
        $this->_initGlobalStyles($cell);

    }
    protected function _initGlobalStyles($cell)
    {
        $fontSize=$this->getStyleVal(self::STYLE_FONT_SIZE);
        if($fontSize){
            
            $this->getStyle($cell)->getFont()->setSize($fontSize);
        }
    }


    public function getCol($cell){
        return Mage::helper('martinexport/excel_cell')->getCol($cell);
        //return $this->_separateColRow($cell,'col');
    }
    public function getRow($cell)
    {
        return Mage::helper('martinexport/excel_cell')->getRow($cell);
        //return $this->_separateColRow($cell,'row');
    }
    

    
    protected function _getActiveSheet()
    {
        $excel=$this->getExcel();
        return $excel->getActiveSheet();
    }
    
    protected function _separateColRow($cell,$return='both')
    {
        return  Mage::helper('martinexport/excel_cell')->separateColRow($cell,$return);
//        $this->_checkCell($cell);
//        $col=preg_replace('/\d/','',$cell);
//        $row=preg_replace('/\D/','',$cell);
//        switch($return)
//        {
//            case  'row':
//                return $row;
//                break;
//            case  'col':
//                return $col;
//                break;
//            default:
//                return array($col,$row);
//        }
    }
    public function getStyle($cell)
    {
        return $this->getExcel()->getActiveSheet()->getStyle($cell);
    }
}
