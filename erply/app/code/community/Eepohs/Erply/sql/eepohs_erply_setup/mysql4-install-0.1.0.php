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
 * Time: 9:49
 */
$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('erply_queue')};
CREATE TABLE {$this->getTable('erply_queue')} (
  `queue_id` int(11) unsigned NOT NULL auto_increment,
  `job_code` varchar(255) NOT NULL default '',
  `store_id` int(10) NOT NULL default '0',
  `total_records` int(10) NOT NULL default '0',
  `records_per_run` int(3) NOT NULL default '20',
  `last_page_no` int(3) NOT NULL default '1',
  `loops_per_run` int(3) NOT NULL default '1',
  `changed_since` int(20) NOT NULL default '0',
  `status` smallint(6) NOT NULL default '0',
  `created_at` datetime NULL,
  `update_at` datetime NULL,
  `scheduled_at` datetime NULL,
  PRIMARY KEY (`queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");

$installer->endSetup();