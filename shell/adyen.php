<?php
/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */

// php adyen.php -action loadBillingAgreements

require_once 'abstract.php';

class Adyen_Payments_Shell extends Mage_Shell_Abstract
{

    /**
   	 * Run script
   	 *
   	 * @return void
   	 */
   	public function run() {
   		$action = $this->getArg('action');
   		if (empty($action)) {
   			print_r($this->usageHelp());
   		} else {
   			$actionMethodName = $action.'Action';
   			if (method_exists($this, $actionMethodName)) {
   				$this->$actionMethodName();
   			} else {
				print_r("Action $action not found!\n");
				print_r($this->usageHelp());
   			}
   		}
   	}

    /**
   	 * Retrieve Usage Help Message
   	 *
   	 * @return string
   	 */
   	public function usageHelp() {
   		$help = 'Available actions: ' . "\n";
   		$methods = get_class_methods($this);
   		foreach ($methods as $method) {
   			if (substr($method, -6) == 'Action') {
   				$help .= '    -action ' . substr($method, 0, -6);
   				$helpMethod = $method.'Help';
   				if (method_exists($this, $helpMethod)) {
   					$help .= $this->$helpMethod();
   				}
   				$help .= "\n";
   			}
   		}
   		return $help;
   	}


	/**
	 * Method to load all billing agreements into Magento.*
	 * @todo move to seperate model so it's easier to call internally
	 * @throws Exception
	 * @throws Mage_Core_Exception
	 */
   	public function loadBillingAgreementsAction()
	{
		$api = Mage::getSingleton('adyen/api');
		$storeId = $this->getArg('store');
		$dateCreated = $this->getArg('createdAfter');

		$stores = Mage::getModel('core/store')->getCollection();
		if ((int)$storeId) {
            $stores->addFieldToFilter('store_id', ['in' => [0, $storeId]]);
		}

		foreach ($stores as $store) {
            /** @var Mage_Core_Model_Store $store */
            print_r(sprintf("Load for store %s\n", $store->getCode()));

			$customerCollection = Mage::getResourceModel('customer/customer_collection');
			$customerCollection->addFieldToFilter('store_id', $store->getId());
			if (isset($dateCreated)) {
				$customerCollection->addFieldToFilter('created_at', ['gteq' => $dateCreated]);
			}
			
			$select = $customerCollection->getSelect();
			$select->reset(Varien_Db_Select::COLUMNS);
			$select->columns(['e.entity_id','e.increment_id']);
			$customerCollection->joinAttribute(
				'adyen_customer_ref',
				'customer/adyen_customer_ref',
				'entity_id', null, 'left'
			);

			$customerReferences = $customerCollection->getConnection()->fetchAssoc($select);
			foreach ($customerReferences as $customerId => $customerData) {

				if ($customerData['adyen_customer_ref']) {
				   $customerReference = $customerData['adyen_customer_ref'];
				} elseif ($customerData['increment_id']) {
				   $customerReference = $customerData['increment_id'];
				} else {
				   $customerReference = $customerId;
				}

				$recurringContracts = $api->listRecurringContracts($customerReference, $store);
                print_r(sprintf("Found %s recurring contracts for customer %s (ref. %s)\n", count($recurringContracts), $customerId, $customerReference));

				$billingAgreementCollection = Mage::getResourceModel('adyen/billing_agreement_collection')
					->addCustomerFilter($customerId)
					->addStoreFilter($store)
                    ->addFieldToFilter('method_code', ['like' => 'adyen_%']);

				//Update the billing agreements
				foreach ($recurringContracts as $recurringContract) {
					/** @var Adyen_Payment_Model_Billing_Agreement $billingAgreement */
					$billingAgreement = $billingAgreementCollection
						->getItemByColumnValue('reference_id', $recurringContract['recurringDetailReference']);

					if (! $billingAgreement) {
						$billingAgreement = Mage::getModel('adyen/billing_agreement');
						$billingAgreement->setCustomerId($customerId);
						$billingAgreement->setStoreId($store->getId());
						$billingAgreement->setStatus($billingAgreement::STATUS_ACTIVE);
					} else {
						$billingAgreement->setStatus($billingAgreement::STATUS_ACTIVE);
						$billingAgreementCollection->removeItemByKey($billingAgreement->getId());
					}

					try {
						$billingAgreement->parseRecurringContractData($recurringContract);
						$billingAgreement->save();
					} catch (Adyen_Payment_Exception $e) {
						print_r(sprintf("Error while adding recurring contract data to billing agreement: %s\n", $e->getMessage()));
						var_dump($recurringContract);
					} catch (Exception $e) {
						throw $e;
					}
				}

				foreach ($billingAgreementCollection as $billingAgreement) {
					$billingAgreement->setStatus($billingAgreement::STATUS_CANCELED);
					$billingAgreement->save();
				}
			}
		}
   	}

    public function listRecurringContractAction()
    {
        $api = Mage::getSingleton('adyen/api');
        $recurringContracts = $api->listRecurringContracts($this->getArg('ref'), $this->getArg('store'));
        print_r($recurringContracts);
    }


	public function pruneBillingAgreementsAction()
	{
		$billingAgreementCollection = Mage::getResourceModel('adyen/billing_agreement_collection')
			->addFieldToFilter('status', Mage_Sales_Model_Billing_Agreement::STATUS_CANCELED);
		foreach ($billingAgreementCollection as $billingAgreement) {
			$billingAgreement->delete();
		}
	}


   	/**
   	 * Display extra help
   	 * @return string
   	 */
   	public function loadBillingAgreementsActionHelp() {
   		return "";
   	}
}


$shell = new Adyen_Payments_Shell();
$shell->run();
