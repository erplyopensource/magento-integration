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
class Eepohs_Erply_Helper_Data extends Mage_Core_Helper_Data
{
    public function log($message)
    {
        if (Mage::getStoreConfig('eepohs_erply/general/log_enabled')) {
            Mage::log($message, null, 'erply_logging_' . (date('Y-m-d')) . '.log');
        }
    }

    public function getErplySession()
    {
        return Mage::getSingleton('core/session')->getErplySession();
    }

    public function setErplySession($data = null)
    {
        Mage::getSingleton('core/session')->setErplySession($data);

        return $this;
    }

    public function unsetErplySession()
    {
        Mage::getSingleton('core/session')->unsErpySession();
        Mage::getSingleton('core/session')->setErpySession(null);

        return $this;
    }
}
