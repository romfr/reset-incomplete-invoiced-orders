<?php
class Rom_ResetIncompleteInvoicedOrders_Model_Crontab
{
    public function runReset($schedule = null) {
        $this->resetMissingInvoices();
    }

    protected function resetMissingInvoices() {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $query = 'SELECT sfo.entity_id as order_id, sfo.increment_id, sfo.created_at, sfo.updated_at, sfo.grand_total'
            . ' FROM sales_flat_order sfo'
            . ' LEFT JOIN sales_flat_invoice sfi'
            . ' ON sfo.entity_id = sfi.order_id'
            . ' WHERE sfi.grand_total IS NULL AND sfo.total_invoiced > 0 AND sfo.status <> "complete"'
            . ' ORDER BY sfo.created_at DESC';
        foreach ($readConnection->fetchAll($query) as $orderToFix) {
            Mage::helper('rom_resetincompleteinvoicedorders')->log(
                'Fix order '.$orderToFix['order_id']."\n".Zend_Json::encode($orderToFix, true)
            );

            $orderResetInvoicedFlagsQuery = 'UPDATE sales_flat_order'
                . ' SET base_discount_invoiced = null, base_shipping_invoiced = null, base_subtotal_invoiced = null, subtotal_invoiced = null,'
                . ' base_tax_invoiced = null, tax_invoiced = null, base_total_invoiced = null, total_invoiced = null, base_total_invoiced_cost = null,'
                . ' base_total_paid = null, total_paid = null, discount_invoiced = null, shipping_invoiced = null, '
                . ' hidden_tax_invoiced = null, base_hidden_tax_invoiced = null'
                . ' WHERE entity_id = '.$orderToFix['order_id'];
            $orderItemsResetInvoicedFlagsQuery = 'UPDATE sales_flat_order_item'
                . ' SET qty_invoiced = 0, tax_invoiced = null, base_tax_invoiced = null, discount_invoiced = null,'
                . ' base_discount_invoiced = null, row_invoiced = null, base_row_invoiced = null,'
                . ' hidden_tax_invoiced = null, base_hidden_tax_invoiced = null'
                . ' WHERE order_id = '.$orderToFix['order_id'];
            $writeConnection->query($orderResetInvoicedFlagsQuery);
            $writeConnection->query($orderItemsResetInvoicedFlagsQuery);

            $this->createInvoice($orderToFix['order_id']);
        }
    }

    protected function createInvoice($orderId) {
        $capture = Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE;
        $order = Mage::getModel('sales/order')->load($orderId);
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        $invoice->setRequestedCaptureCase($capture);
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);

        $transaction = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());

        $transaction->save();
    }
}