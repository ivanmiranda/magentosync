<?php

use Sincco\Sfphp\Config\Reader;

class CustomersCommand extends Sincco\Sfphp\Abstracts\Command {

	public function ($tipo = 'database', $base = 'default') {
		$model = $this->getModel('Default');
		$query = "SELECT * FROM CA_vw_Magento_CustomerInformation WHERE MODIFDT>'2018-01-01'";
		var_dump($model->getData("select top 10 * from CA_vw_Magento_CustomerInformation;"));
	}

}