<?php
class Martin_Recommend_Adminhtml_Recommend_IndexController extends
    Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('catalog')
            ->_addBreadcrumb($this->__('recommend'), $this->__('Manage Recommend'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_title($this->__('Manage Recommend'));
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('recommend/adminhtml_recommend'))
            ->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()
            ->setBody($this->getLayout()
                ->createBlock('recommend/adminhtml_price_grid')
                ->toHtml()
            );
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $this->_title($this->__('Add Recommend'));

        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('recommend/recommend');

        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('This Item no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $this->_title($model->getId() ? $model->getCountry() : $this->__('New Item'));

        // Restore previously entered form data from session
        $data = Mage::getSingleton('adminhtml/session')->getItemData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        Mage::register('shipping_price', $model);

        if (isset($id)) {
            $breadcrumb = $this->__('Edit');
        } else {
            $breadcrumb = $this->__('New');
        }
        $this->_initAction()
            ->_addBreadcrumb($breadcrumb, $breadcrumb);

        $this->_addContent(
            $this->getLayout()->createBlock('recommend/adminhtml_recommend_edit')
        );

        $this->renderLayout();
    }

    public function saveAction(){
        if ($postData = $this->getRequest()->getPost()) {
            $adapter = Mage::getSingleton('core/resource')->getConnection('core_write');

            $name = $postData['name'];
            $url = $postData['url'];
            $skus = $postData['skus'];
            $skus = preg_replace("[\s+]",',',trim($postData['skus']));

            $url_parse = parse_url($url);
            if(isset($url_parse['path'])) {
                $url_path = substr($url_parse['path'],4);
                $url_path = trim($url_path, '/');
                $url_result = $adapter->query("SELECT * FROM `core_url_rewrite` WHERE request_path='".$url_path."' limit 1");
                $url = $url_result->fetch();
                if($url&&isset($url['category_id'])){
                    $category_id = $url['category_id'];

                    $skus_arr = explode(',',$skus);
                    $include_sku = [];
                    $exclude_sku = [];

                    foreach ($skus_arr as $sku){
                        $product_result = $adapter->query("select entity_id from catalog_product_entity where sku='".$sku."'");
                        $product = $product_result->fetch();
                        if($product){
                            $product_id = $product['entity_id'];
                            $include_sku[$product_id] = $sku;
                        }else{
                            $exclude_sku[] = $sku;
                            Mage::getSingleton('adminhtml/session')->addError($sku.' does not exist');
                        }
                    }

                    if(count($include_sku)>0) {
                        $data = array(
                            'name' => $name,
                            'url' => trim($postData['url']),
                            'category_id' => $category_id,
                            'store_id' => 0,
                            'skus_str' => implode(',',$include_sku)
                        );

                        $model = Mage::getModel('recommend/recommend')
                            ->setData($data);

                        try {
                            $model->save();
                            $recommend_id = $model->getId();
                            $i = count($include_sku)+1;
                            foreach ($include_sku as $product_id=>$sku){
                                $row = [];
                                $row = array(
                                    'recommend_id'=>$recommend_id,
                                    'product_id'=>$product_id,
                                    'position'=>$i
                                );
                                $i--;
                                $adapter->insert('catalog_product_recommend_relation',$row);
                            }
                        } catch (Exception $e) {
                            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                            Mage::getSingleton('adminhtml/session')->setRuleData($data);

                            return $this->_redirect('*/*/edit', array('tag_id' => $model->getId(), 'store' => $model->getStoreId()));
                        }
                    }
                }
            }
        }
        return $this->_redirect('*/*/index', array('_current' => true));
    }

    public function deleteAction(){
        if ($postData = $this->getRequest()->getPost()) {
            foreach($postData['id'] as $id){
                $model  = Mage::getModel('bcshipping/price')->load($id);
                if($model){
                    $model->delete();
                }
            }
        }

        Mage::dispatchEvent('bcshipping_rule_save_after',array());

        return $this->_redirect('*/*/index', array('_current' => true));
    }

    protected function _isAllowed(){
        return Mage::getSingleton('admin/session')->isAllowed('catalog/recommend');
    }
}