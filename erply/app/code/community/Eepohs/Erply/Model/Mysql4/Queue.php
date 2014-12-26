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
 * Time: 9:46
 */
class Eepohs_Erply_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('Erply/queue', 'queue_id');
    }
}