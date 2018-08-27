<?php

use Sincco\Sfphp\Config\Reader;

class SalesCommand extends Sincco\Sfphp\Abstracts\Command {

	public function orders () {
		$this->helper('Log')->log('SYNC :: Orders ================ >>');
		$orders = $this->helper('ApiConsumer')->getOrders();
		
		foreach ($orders as $order) {
			$this->helper('Log')->log('Register order ' . $order['increment_id']);
			
		}

		$model = $this->getModel('Default');
		
		$this->helper('Log')->log('<< =========== END Orders SYNC');
	}

}