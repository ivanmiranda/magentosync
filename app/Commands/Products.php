<?php

use Sincco\Sfphp\Config\Reader;

class ProductsCommand extends Sincco\Sfphp\Abstracts\Command {

	public function stock () {
		$this->helper('Log')->log('SYNC :: Products Stock ================ >>');
		$model = $this->getModel('Default');
		$query = "
			SELECT ITEMNMBR, STNDCOST, CURRCOST, TAXOPTNS, PRCLEVEL, MAX(BGNGQTY) QTY 
			FROM CA_vw_Magento_InventoryItems 
			WHERE ITEMNMBR = '1801-01'
			GROUP BY ITEMNMBR, STNDCOST, CURRCOST, TAXOPTNS, PRCLEVEL;";
		foreach ($model->getCollection($query) as $product) {
			$response = $this->helper('ApiConsumer')->updStock(trim($product->ITEMNMBR), trim($product->QTY));
			if (!$response) {
				$this->helper('Log')->log('ERROR when sync product ' . trim($product->ITEMNMBR) . ' with Magento2');
			}
		}
		$this->helper('Log')->log('<< =========== END Products Stock SYNC');
	}

}