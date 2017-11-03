<?php

require_once dirname($_SERVER['SCRIPT_NAME']) . DIRECTORY_SEPARATOR . 'abstract.php';

class Rom_ResetIncompleteInvoicedOrders_Shell extends Mage_Shell_Abstract
{
    public function run()
    {
        $crontab = Mage::getModel('rom_resetincompleteinvoicedorders/crontab');
        $crontab->runReset();
    }
}

$shell = new Rom_ResetIncompleteInvoicedOrders_Shell();
$shell->run();
