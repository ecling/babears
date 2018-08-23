<?php
class Martin_SalesReports_Model_Resource_Order_Item_Collection extends Mage_Sales_Model_Resource_Order_Item_Collection{
    public function getSelectCountSql(){
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::HAVING);
        $countSelect->columns("COUNT(DISTINCT main_table.product_id)");

        return $countSelect;
    }

    public function getTotalPrice(){
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        $countSelect->reset(Zend_Db_Select::GROUP);
        $countSelect->reset(Zend_Db_Select::HAVING);
        $countSelect->columns("SUM(main_table.base_row_total)");

        $total = $this->getConnection()->fetchOne($countSelect);

        return (string)$total;
    }
}