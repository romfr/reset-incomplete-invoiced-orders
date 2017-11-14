<?php
class Rom_ResetIncompleteInvoicedOrders_Helper_Data extends Mage_Core_Helper_Abstract
{
    const LOG_FILE = 'rom_resetincompleteinvoicedorders.log';

    public function log($message) {
        Mage::log($message, null, self::LOG_FILE);
    }
}