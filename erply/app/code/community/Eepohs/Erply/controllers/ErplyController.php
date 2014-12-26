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
class Eepohs_Erply_ErplyController extends Mage_Adminhtml_Controller_Action
{

    public function indexAction()
    {
        // Load layout
        $this->loadLayout();
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        // Add left sidebar
        $this->_addLeft(
            $this->getLayout()
                ->createBlock('Eepohs_Erply_Block_SidebarBlock')
        );
        // Render layout
        $this->renderLayout();
    }

    public function scheduleImportAction()
    {

        $data = $this->getRequest()->getPost();
        $queueData["type"] = $data["import_type"];
        $queueData["storeId"] = $data["store_id"];
        /*
         * Schedule Mass Import to next cron runtime.
         */
        $runEvery = Mage::getStoreConfig('eepohs_erply/queue/run_every', $data["store_id"]);
        //        $scheduleDate = $data["scheduled_ate"];
        $now = Mage::getModel('core/date')->gmtTimestamp();
        //        Mage::helper('Erply')->log("Timestamp: ".$now);
        $minutes = date('i', $now);
        $minutes = round(($minutes + $runEvery / 2) / $runEvery) * $runEvery;
        $hours = date('H', $now);
        if ($minutes < 10) {
            $minutes = "0" . $minutes;
        }
        if ($minutes == 60) {
            $minutes = "00";
            $hours = date("H", strtotime('+1 hours', $now));
        }

        $scheduleDateTime = date('Y-m-d ' . $hours . ':' . $minutes . ':00', $now);
        $queueData["scheduleDateTime"] = $scheduleDateTime;
        /*
         * If there are any Queue's with same run code, then let's delete them
         */
        Mage::getModel('Erply/Queue')
            ->deleteQueueByCode('erply_' . $queueData["type"]);

        Mage::getModel('Erply/Queue')->addQueue($queueData);
        Mage::getSingleton('core/session')->addSuccess(Mage::helper('Erply')
            ->__('Import for %s has been scheduled at %s!', $queueData["type"], $queueData["scheduleDateTime"]));
        //$this->_redirectUrl($this->getUrl('Erply/Index'));
        $this->_redirect('Erply/Erply', $arguments = array());
        //$this->_redirect();
    }
}
