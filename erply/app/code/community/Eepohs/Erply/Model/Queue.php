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
 * Time: 9:45
 */
class Eepohs_Erply_Model_Queue extends Eepohs_Erply_Model_Erply
{

    private $_storeId = 0;

    public function _construct()
    {
        parent::_construct();
        $this->_init('Erply/queue');
    }

    public function addQueue($data, $cron = true)
    {
        $params = array();
        $queue = new self();
        $this->_storeId = $data["storeId"];
        //$this->closeOpenQueues('erply_'.$data["type"]);
        $activeQueue = $this->loadActive('erply_' . $data["type"]);
        if (count($activeQueue) > 0) {
            return false;
        }
        if (isset($data["changedSince"])) {
            $lastQueue = $this->getCollection()
                ->addFieldToFilter('job_code', array('eq' => 'erply_' . $data["type"]))
                ->addFieldToFilter('status', array('eq' => 0))
                ->addFieldToFilter('store_id', array('eq' => $data["storeId"]));
            $last = $lastQueue->getLastItem();
            if ($last->getId()) {
                $params["changedSince"] = strtotime($last->getCreatedAt());
            } else {
                $params["changedSince"] = strtotime("-24hour", time());
            }
            $queue->setChangedSince($params["changedSince"]);
        }
        $data["totalRecords"] = Mage::getModel('Erply/Import')
            ->getTotalRecords($data["storeId"], $data["type"], $params);
        if ($data["totalRecords"] == 0) {
            return false;
        }
        $data["recordsPerRun"] = Mage::getStoreConfig('eepohs_erply/queue/records_per_run', $data["storeId"]);

        if ($data["type"] == 'image_import') {
            $runEvery = Mage::getStoreConfig('eepohs_erply/queue/run_every', $data["storeId"]);
            $data["recordsPerRun"] = floor(450 / (60 / $runEvery));
        } elseif ($data["type"] == 'price_update') {
            $data["recordsPerRun"] = $data["recordsPerRun"] * 5;
        } elseif ($data["type"] == 'inventory_update') {
            $data["recordsPerRun"] = $data["recordsPerRun"] * 5;
        }

        $data["loopsPerRun"] = Mage::getStoreConfig('eepohs_erply/queue/loops_per_run', $data["storeId"]);

        $queue->setJobCode('erply_' . $data["type"]);
        $queue->setStoreId($data["storeId"]);
        $queue->setTotalRecords($data["totalRecords"]);
        $queue->setRecordsPerRun($data["recordsPerRun"]);
        $queue->setLastPageNo(0);
        $queue->setLoopsPerRun($data["loopsPerRun"]);
        $queue->setStatus(1);
        $queue->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
        $queue->setUpdatedAt(strftime('%Y-%m-%d %H:%M:%S', time()));
        $queue->setScheduledAt($data["scheduleDateTime"]);
        $queue->save();

        if ($cron) {
            Mage::getModel('Erply/Cron')
                ->addCronJob('erply_' . $data["type"], $data["scheduleDateTime"]);
            // $this->_redirectSuccess("Cron Job added!");
        }

        return true;
    }

    public function loadActive($jobCode)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('job_code', array('eq' => $jobCode))
            ->addFieldToFilter('status', array('eq' => 1));
        if ($this->_storeId > 0) {
            $collection->addFieldToFilter('store_id', array('eq' => $this->_storeId));
        }

        return $collection;
    }

    public function deleteQueueByCode($jobCode)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('job_code', array('eq' => $jobCode));
        if (count($collection) > 0) {
            foreach ($collection as $queue) {
                $queue->delete();
            }
        }
        $pending = Mage::getModel('cron/schedule')->getCollection()
            ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
            ->addFieldToFilter('job_code', $jobCode)
            ->load();
        if (count($pending) > 0) {
            foreach ($pending as $job) {
                $job->delete();
            }
        }
    }

    protected function closeOpenQueues($jobCode)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('job_code', array('eq' => $jobCode))
            ->addFieldToFilter('status', array('eq' => 1))
            ->addFieldToFilter('store_id', array('eq' => $this->_storeId));
        foreach ($collection as $queue) {
            $obj = new self();
            $obj->load($queue->getId());
            $obj->setStatus(0);
            $obj->save();
        }
    }
}
