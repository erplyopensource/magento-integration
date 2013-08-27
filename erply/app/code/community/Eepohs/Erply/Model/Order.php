<?php
/**
 * NB! This is a BETA release of Erply Connector.
 *
 * Use with caution and at your own risk.
 *
 * The author does not take any responsibility for any loss or damage to business
 * or customers or anything at all. These terms may change without further notice.
 *
 * License terms are subject to change. License is all-restrictive until
 * said otherwise.
 *
 * @author Eepohs Ltd
 */
/**
 * Created by Rauno Väli
 * Date: 27.03.12
 * Time: 10:25
 */
class Eepohs_Erply_Model_Order extends Mage_Core_Model_Abstract
{

    private $_storeId;
    protected static $_defaultInvoiceState = 'PENDING';
    protected static $_invoiceStatesAry = array(
        'PENDING' => 'PENDING',
        'PROCESSING' => 'READY'
    );

    public function _construct()
    {
        parent::_construct();
    }

    public function prepareOrder($order, $erpOrder = false, $storeId)
    {

        $this->_storeId = $storeId;

        Mage::helper('Erply')->log("Customer ID on order: " . $order['customer_id']);
        Mage::helper('Erply')->log($order["billing_address"]);
        $this->_data = array();
        $erpAttributes = array();

        // if order is synchronized then update
        if ( $erpOrder ) {
            $this->_data['id'] = $erpOrder['id'];
            if ( isset($this->_data['attributes']) ) {
                $erpAttributes = $this->_data['attributes'];
                $this->offsetUnset('attributes');
            }
        }

        // Customer may or may not exist. In case of guest checkout there is no
        // customer record and we have to make a new Erply customer by Magento
        // billing information.
        if ( isset($order['customer_id']) && !empty($order['customer_id']) ) {
            // Customer exists. Synchronize customer before procceeding.
            $customer = Mage::getSingleton('Erply/Customer');
            $customerId = $customer->getCustomerExists($order['customer_email'], $storeId);

            // check if order customer synchronized
            if ( !$customerId ) {
                if ( !$customer->addNewCustomer($order['customer_id'], $storeId) ) {
//                    throw new Exception("Couldn't add new customer");
                    Mage::helper('Erply')->log(sprintf('%s(%s): Couldn not add new customer', __METHOD__, __LINE__));
                }
            }

            if ( !empty($customerId) ) {
                $this->_data['customerID'] = $this->_data['payerID'] = $customerId;

                // payerAddressID
                $address = Mage::getModel('Erply/Address');
                $billingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/billing_address', $this->_storeId);
                $shippingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/shipping_address', $this->_storeId);

                $this->_data['payerAddressID'] = $address->saveCustomerAddress($customerId, $billingAddressTypeId, $order["billing_address"], $this->_storeId);
                $this->_data['addressID'] = $address->saveCustomerAddress($customerId, $shippingAddressTypeId, $order["shipping_address"], $this->_storeId);
            }
        } else {
            //Customer Checkout
            $customerData = array(
                'firstName' => $order['billing_address']['firstname']
                , 'lastName' => $order['billing_address']['lastname']
                , 'email' => $order['customer_email']
                , 'phone' => $order['billing_address']['telephone']
                , 'fax' => $order['billing_address']['fax']
                , 'notes' => 'Created from Magento'
            );
            $customerId = Mage::getModel('Erply/Customer')->sendCustomer($customerData, $this->_storeId);
            if ( !empty($customerId) ) {
                $this->_data['customerID'] = $this->_data['payerID'] = $customerId;

                // payerAddressID
                $address = Mage::getModel('Erply/Address');
                $billingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/billing_address', $this->_storeId);
                $shippingAddressTypeId = Mage::getStoreConfig('eepohs_erply/customer/shipping_address', $this->_storeId);

                $this->_data['payerAddressID'] = $address->saveCustomerAddress($customerId, $billingAddressTypeId, $order["billing_address"], $this->_storeId);
                $this->_data['addressID'] = $address->saveCustomerAddress($customerId, $shippingAddressTypeId, $order["shipping_address"], $this->_storeId);
            }
        }

        // type
//        if (empty($erpOrder) || empty($erpOrder['type'])) {
        $this->_data['type'] = 'ORDER'; //$this->erpOrderType;
//        } else {
//            $this->_data['type'] = $erpOrder['type'];
//        }
        // currencyCode
        $this->_data['currencyCode'] = $order['order_currency_code'];

        // date
        $this->_data['date'] = date('Y-m-d', strtotime($order['created_at']));

        // time
        $this->_data['time'] = date('H:m:s', strtotime($order['created_at']));

        // invoiceState
        $orderState = strtoupper($order['status']);
        $this->_data['invoiceState'] = isset($this->_invoiceStatesAry[$orderState]) ? self::$_invoiceStatesAry[$orderState] : self::$_defaultInvoiceState;

        // internalNotes
        $this->_data['internalNotes'] = 'Magento Order #' . $order['increment_id'];

        // Get gift message into internal notes
        if ( !empty($order['gift_message_id']) ) {
            $giftMessageMod = Mage::getModel('giftmessage/message')->load((int) $order['gift_message_id']);
            $this->_data['internalNotes'] .= "\n\n" . sprintf('
Gift Message
--------------------------------
From: %s
To: %s
Message: %s', $giftMessageMod->getSender(), $giftMessageMod->getRecipient(), $giftMessageMod->getMessage());
        }

        // invoiceNo. Only set if new order and number must be numeric.
        if ( !isset($this->_data['id'])
            // && (int)Mage::getStoreConfig('erply/sync_config/use_magento_document_no') == 1
            && is_numeric($order['increment_id'])
        ) {
            $this->_data['invoiceNo'] = $order['increment_id'];
        }

        // Employee
//        $employeeId = $this->erply->getUserData('employeeID');
        $employeeId = 0;
        if ( !empty($employeeId) ) {
            $this->_data['employeeID'] = $employeeId;
        }

        // Warehouse
        $erplyWarehouseId = (int) Mage::getStoreConfig('erply/sync_config/erply_warehouse_id');
        if ( false && $erplyWarehouseId > 0 ) {
            $this->_data['warehouseID'] = $erplyWarehouseId;
        }

        // Payment type
        $this->_data['paymentType'] = "CARD";

        // Confirmed
        $this->_data['confirmInvoice'] = 0;

        /*
         * Items
         */
        $productModel = Mage::getModel('Erply/Product');
        $key = 1;
        $erpVatrates = $this->getVatRates();
        if ( !empty($erpVatrates) ) {
            $erpVatrates = $this->toKeyValueArray($erpVatrates, 'rate', array('id'));
            foreach ( $order['items'] as $product ) {
                // check if product is synchronized
//			$erpProductId = $productModel->erpProductExists($product['product_id']);
                $erpProductId = null;
                if ( ($erpProduct = $productModel->findProduct($product["sku"]) ) ) {
                    $erpProductId = $erpProduct['productID'];
                } else {
                    // Sync product.
//                if (($product2 = $productModel->magProductInfo($product['product_id']))) {
//                    $erpProductId = $productModel->m2eSyncProduct($product2);
//                } else {
//                }
                }

                if ( !empty($erpProductId) ) {
                    $this->_data['productID' . $key] = $erpProductId;
                }
                // magento product is not synchronized, add info to internal notes
//            else{
//                $this->_data['internalNotes'] .= 'Magento product ' . $product['name'] . ', id - ' . $product['product_id'] . ' is not synchronized with Erply.';
//            }

                $this->_data['itemName' . $key] = $product['name'];
                if ( isset($erpVatrates[(int) $product['tax_percent']]) ) {
                    $this->_data['vatrateID' . $key] = $erpVatrates[(int) $product['tax_percent']];
                } else {
                    $this->_data['vatrateID' . $key] = $erpVatrates[0];
                }
                $this->_data['amount' . $key] = (int) $product['qty_ordered'];
                $this->_data['price' . $key] = $product['price'];
                $this->_data['discount' . $key] = (int) $product['discount_percent'];
                $key++;

                /*
                  if($erpProductId){
                  $this->_data['productID' . $key] = $erpProductId;
                  $this->_data['itemName' . $key] = $product['name'];
                  if(isset($erpVatrates[(int)$product['tax_percent']])){
                  $this->_data['vatrateID' . $key] = $erpVatrates[(int)$product['tax_percent']];
                  }else{
                  $this->_data['vatrateID' . $key] = $erpVatrates[0];
                  }
                  $this->_data['amount' . $key] = (int)$product['qty_ordered'];
                  $this->_data['price' . $key] = $product['price'];
                  $this->_data['discount' . $key] = (int)$product['discount_percent'];
                  $key++;
                  }
                 */
            }
        }
        // add shipping costs if any
        if ( isset($order['shipping_amount']) ) {
            $s = Mage::getModel('sales/quote_address_rate')->getCollection();
            foreach ( $s as $s1 ) {
                if ( $s1->getCode() == $order["shipping_method"] ) {
                    break;
                }
            }
            $shippingDescription = isset($s1["method_title"]) ? '(' . $s1["method_title"] . ')' : '';
            $this->_data['itemName' . $key] = 'Shipping ' . $shippingDescription;
            $this->_data['amount' . $key] = 1;
            $this->_data['price' . $key] = $order['shipping_amount'];

            // Vatrate
            if ( (float) $order['shipping_tax_amount'] > 0 ) {
                $rate = ((($order['shipping_incl_tax'] / $order['shipping_amount']) - 1) * 100);
                $rate = round($rate, 0);
            } else {
                // Free shipping
                $rate = 0;
            }
            $this->_data['vatrateID' . $key] = isset($erpVatrates[$rate]) ? $erpVatrates[$rate] : $erpVatrates[0];
        }

        // Add shipment info if invoice.
        $this->_data['notes'] = $order["shipping_description"];
//        if (false && (int)Mage::getStoreConfig('erply/sync_config/order_export_logic') == 3) {
//            $trackingInfo = Mage::helper('erply')->getOrderShipmentTrackingInfo($order['order_id']);
//            if (is_array($trackingInfo)) {
//                // Add to notes
//                $this->_data['notes'] .= 'Shipment carrier: ' . strtoupper($trackingInfo['carrier_code']) . "\n";
//                $this->_data['notes'] .= 'Shipment tracking#: ' . $trackingInfo['number'] . "\n\n";
//
//                // Add to attributes
//                $erpAttributes = $this->erpSetAttribute(array(
//                    'attributeName' => '_e_shipment_carrier',
//                    'attributeType' => 'text',
//                    'attributeValue' => strtoupper($trackingInfo['carrier_code'])
//                ), $erpAttributes);
//                $erpAttributes = $this->erpSetAttribute(array(
//                    'attributeName' => '_e_shipment_tracking_code',
//                    'attributeType' => 'text',
//                    'attributeValue' => $trackingInfo['number']
//                ), $erpAttributes);
//            }
//        }
//        $erpAttributes = $this->erpConvertAttributes($erpAttributes);
//        $this->_data = array_merge($this->_data, $erpAttributes);

        return $this->_data;
    }

    protected function getVatRates()
    {
        $erply = Mage::getModel('Erply/Erply');
        $erply->verifyUser($this->_storeId);
        $vatRates = $erply->sendRequest('getVatRates');
        $vatRates = json_decode($vatRates, true);
        if ( $vatRates["status"]["responseStatus"] == "ok" ) {
            return $vatRates["records"];
        } else {
            return false;
        }
    }

    /*
     * Function converts input associative array data to plain array with one input array value as key
     * and array of values as value
     * @param $array|array - array to convert
     * @param $key|string - the name of $array value to become a key
     * @param $value|array - an array of name values of $array to become a value
     * @return $newarray|array
     */

    protected function toKeyValueArray($array, $key, $valueArr)
    {
        $newarray = array();
        foreach ( $array as $item ) {
            foreach ( $valueArr as $value ) {
                if ( count($valueArr) == 1 ) {
                    $newarray[$item[$key]] = $item[$value];
                } else {
                    $newarray[$item[$key]][$value] = $item[$value];
                }
            }
        }
        return $newarray;
    }

}
