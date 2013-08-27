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
 * Time: 8:56
 */
class Eepohs_Erply_Model_Observer extends Mage_Core_Model_Abstract
{

    const XML_PATH_SCHEDULE_AHEAD_FOR = 'system/cron/schedule_ahead_for';

    public function checkSchedule()
    {
        $scheduleAheadFor = Mage::getStoreConfig(self::XML_PATH_SCHEDULE_AHEAD_FOR) * 60;
        $stores = Mage::getResourceModel('core/store_collection')
            ->load()->toOptionArray();
        $jobs = array();
        /*
         * Automatic update is done only for product data and inventory.
         */
        $jobCodes = array(
            'product' => 'erply_product_import',
//            'category' => 'erply_category_import',
            'inventory' => 'erply_inventory_update',
            'price' => 'erply_price_update',
//            'image' => 'erply_image_import'
        );

        $pending = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
            ->load();
        $activeQueues = Mage::getModel('Erply/Queue')->getCollection()
            ->addFieldToFilter('status', 1)
            ->load();
        $exists = array();
        foreach ( $pending->getIterator() as $schedule ) {
            $exists[$schedule->getJobCode() . '/' . $schedule->getScheduledAt()] = 1;
//            if(in_array($schedule->getJobCode(), $jobCodes)) {
//                unset($jobCodes[array_search($schedule->getJobCode(), $jobCodes)]);
//            }
        }
        foreach ( $activeQueues as $queue ) {
            if ( in_array($queue->getJobCode(), $jobCodes) ) {
                unset($jobCodes[array_search($queue->getJobCode(), $jobCodes)]);
            }
        }
        foreach ( $jobCodes as $key => $jobCode ) {
//
            $productUpdateSchedule = Mage::getStoreConfig('eepohs_erply/update_schedule/'.$key.'_update_schedule');
            if(empty($productUpdateSchedule)) {
                return false;
            }



            Mage::helper('Erply')->log("Setting Erply Cron for ".$jobCode);
            $cronSchedule = Mage::getModel('cron/schedule');


            $cronSchedule->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING);
            $cronSchedule->setCronExpr($productUpdateSchedule);
            $cronSchedule->setJobCode($jobCode);

            $now = time();
            $timeAhead = $now + $scheduleAheadFor;
            for ($time = $now; $time < $timeAhead; $time += 60) {
                $ts = strftime('%Y-%m-%d %H:%M:00', $time);
                if (!empty($exists[$jobCode . '/' . $ts])) {
                    // already scheduled
                    continue;
                }
                $queueAdded = false;
                if ($result = $cronSchedule->trySchedule($time)) {
                    foreach ($stores as $store) {
                        $storeId = $store["value"];
                        if(Mage::getStoreConfig('eepohs_erply/account/enabled',$storeId)){
                        if($key == "product" && Mage::getStoreConfig('eepohs_erply/update_schedule/only_main')) {
                            if(!Mage::getStoreConfig('eepohs_erply/account/is_main',$storeId)) {
                                continue;
                            }
                        }
                            $queueData["type"] = str_replace('erply_', '', $jobCode);
                            $queueData["storeId"] = $storeId;
                            $queueData["scheduleDateTime"] = $ts;
                            $queueData["changedSince"] = "last";
                            $queue = Mage::getModel('Erply/Queue')->addQueue($queueData, false);
                            if(!$queueAdded) $queueAdded = $queue;
                        }
                    }
                    if($queueAdded) {
                        $cronSchedule->unsScheduleId()->save();

                        $exists[$jobCode . '/' . $ts] = 1;
                    }
                } else {
                    continue;
                }
            }

//            }
        }
    }

    public function sendOrder($observer) {
        $event = $observer->getEvent();
        $order = $event->getInvoice()->getOrder();
        $incrementId = $order->getIncrementId();
        $storeId = $order->getStoreId();
        if(Mage::getStoreConfig('eepohs_erply/account/disable_order', $storeId)) {
            Mage::helper('Erply')->log("Sending order to erply is disabled for store: #".$storeId);
            return false;
        }
        if($incrementId){
            try {
                //$data["id"] = $incrementId;
                //$data["id"] = $incrementId;
                $orderData = Mage::getModel('sales/order_api')->info($incrementId);
                $e = Mage::getModel('Erply/Erply');
                $storeId = $order->getStoreId();
                $e->verifyUser($storeId);
                $data = Mage::getModel('Erply/Order')->prepareOrder($orderData, false, $storeId);
                if ( $data ) {
                    $response = $e->sendRequest('saveSalesDocument', $data);
                    Mage::helper('Erply')->log("Saving order data to erply:" . print_r($data, true));
                    $response = json_decode($response, true);
                    if($response["status"]["responseStatus"] == "ok") {
                        $documentId = $response["records"][0]["invoiceID"];
                        $this->sendPayment($data, $storeId, $documentId);
                        Mage::helper('Erply')->log("Erply reponse on order save:" . var_export($response, true));
                        Mage::helper('Erply')->log("Saved order to erply: #" . $response['records'][0]['invoiceID']);
                        Mage::helper('Erply')->log("Erply documentId is: ".$documentId);
                    }

                    return $response['records'][0]['invoiceID'];
                } else {
                    Mage::helper('Erply')->log("Failed to send order");
                    return false;
                }
            } catch (Exception $e) {
                Mage::helper('Erply')->log("Failed to send order to Erply with message: ".$e->getMessage());
                Mage::helper('Erply')->log("Exception trace: ".$e->getTraceAsString());
            }
        }
    }

    protected function sendPayment($orderData, $storeId, $documentId) {
        try {
            $e = Mage::getModel('Erply/Erply');
            $e->verifyUser($storeId);
            $orderData["documentID"] = $documentId;
            $paymentData = Mage::getModel('Erply/Payment')->preparePayment($orderData, $storeId);
            if($paymentData) {
                Mage::helper('Erply')->log("Erply - sending payment data: ".print_r($paymentData, true));
                $response = $e->sendRequest('savePayment', $paymentData);
                $response = json_decode($response, true);
                Mage::helper('Erply')->log("Erply payment saving reponse: ".print_r($response, true));
                if($response["status"]["responseStatus"] == "ok") {
                    Mage::helper('Erply')->log("Erply payment saving was successful");
                }
            }
        } catch (Exception $e) {
            Mage::helper('Erply')->log("Failed to create payment for order: ".$e->getMessage());
        }
    }

}
