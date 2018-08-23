<?php 
class Tm_GoogleMap_Block_MarkerAttributes extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

    public function __construct()
    {
        // create columns
         $this->addColumn('title', array(
            'label' => Mage::helper('adminhtml')->__('Title'),
            'size' => 20,
            'comment' => 'test'
        ));
        $this->addColumn('coordinates', array(
            'label' => Mage::helper('adminhtml')->__('Coordinates'),
            'size' => 22,
        ));
        $this->addColumn('image', array(
            'label' => Mage::helper('adminhtml')->__('Image'),
            'size' => 20,
        ));
        $this->addColumn('infowindow', array(
            'label' => Mage::helper('adminhtml')->__('Infowindow'),
            'size' => 28,
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Marker');

        parent::__construct();
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($columnName == 'infowindow') {
            $rendered = '<textarea name="' . $inputName . '" rows="1" ' . ($column['size'] ? 'cols="' . $column['size'] . '"' : '') . ' style="height:40px;" />#{' . $columnName . '}</textarea>';
        } else {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '/>';
        }

        return $rendered;
    }
}
?>