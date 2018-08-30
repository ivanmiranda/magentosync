<?php

use Sincco\Sfphp\Config\Reader;

class SalesCommand extends Sincco\Sfphp\Abstracts\Command {

	private function getNextId() {
		$model = $this->getModel('Default');
		$number = $model->getData("SELECT * FROM SOP40300 WHERE DOCTYABR = 'ORD';");
		$number = array_pop($number);

		$query = "
		DECLARE	@return_value int,
				@O_vSopNumber varchar(21),
				@O_iErrorState int;

		EXEC	@return_value = taGetSopNumber
				@I_tSOPTYPE = 2,
				@I_cDOCID = N'''00001''',
				@I_tInc_Dec = 1,
				@O_vSopNumber = @O_vSopNumber OUTPUT,
				@O_iErrorState = @O_iErrorState OUTPUT;

		SELECT	@O_vSopNumber as N'@O_vSopNumber',
				@O_iErrorState as N'@O_iErrorState';";
		$model->getData($query);

		return $number['SOPNUMBE'];
	}

	private function getCustomerId($id) {
		$model = $this->getModel('Default');

		$customer = $this->helper('ApiConsumer')->getCustomer($id);
		$query = "SELECT * FROM CA_vw_Magento_CustomerInformation WHERE CUSTNAME='" . $customer['firstname'] . " " . $customer['lastname'] . "';";
		$_customerData = $model->getData($query);
		if (count($_customerData) > 0) {
			return "WEB" . $customer['id']; //$_customerData['CUSTNMBR'];
		} else {
			$address = $customer['addresses'][0];
			$query = "
				DECLARE	@return_value int,
						@O_iErrorState int,
						@oErrString varchar(255)

				EXEC	@return_value = taUpdateCreateCustomerRcd
						@I_vCUSTNMBR = N'WEB" . $customer['id'] . "',
						@I_vCUSTNAME = N'" . $customer['firstname'] . " " . $customer['lastname'] . "',
						@I_vADRSCODE='PRIMARY',
						@I_vSHRTNAME = N'" . $customer['email'] . "',
						@I_vADDRESS1 = N'" . $address['street'][0] . "',
						@I_vADDRESS2 = N'" . (isset($address['street'][1]) ? $address['street'][1] : " ") . "',
						@I_vADDRESS3 = N'" . (isset($address['street'][2]) ? $address['street'][2] : " ") . "',
						@I_vCITY = N'" . $address['city'][0] . "',
						@I_vSTATE = N'" . $address['region']['region'] . "',
						@I_vZIPCODE = N'" . $address['postcode'] . "',
						@I_vCCode = N'" . $address['region']['region_code'] . "',
						@I_vPHNUMBR1 = N'" . $address['telephone'] . "',
						@I_vCRLMTTYP = 1,
						@O_iErrorState = @O_iErrorState OUTPUT,
						@oErrString = @oErrString OUTPUT

				SELECT	@O_iErrorState as N'@O_iErrorState',
						@oErrString as N'@oErrString'

				SELECT	'Return Value' = @return_value
			";
			$response = $model->getData($query);
			if (isset($response['ERROR'])) {
				$this->helper('Log')->log($query);
				return "WEB" . $customer['id'];
			} else {
				return "WEB" . $customer['id'];
			}
		}
	}

	private function orderHdr($order) {
		$model = $this->getModel('Default');
		$address = $order['extension_attributes']['shipping_assignments'][0]['shipping']['address'];

		$query = "
		DECLARE	@return_value int,
				@O_iErrorState int,
				@oErrString varchar(255);

		EXEC	@return_value = taSopHdrIvcInsert
		@I_vSOPTYPE = 2,
		@I_vDOCID = N'" . $order['SOPNUMBER'] . "',
		@I_vSOPNUMBE = N'" . $order['SOPNUMBER'] . "',
		@I_vORIGNUMB = N'" . $order['increment_id'] . "',
		@I_vTAXAMNT = " . $order['tax_amount'] . ",
		@I_vLOCNCODE = N'" . $address['postcode'] . "',
		@I_vDOCDATE = '" . $order['created_at'] . "',
		@I_vCUSTNMBR = '" . $order['CUSTNMBR'] . "',
		@I_vCUSTNAME = N'" . $order['customer_firstname'] . " " . $order['customer_lastname'] . "',
		@I_vCSTPONBR = N'" . $order['increment_id'] . "',
		@I_vShipToName = N'" . $order['customer_firstname'] . " " . $order['customer_lastname'] . "',
		@I_vADDRESS1 = N'" . $address['street'][0] . "',
		@I_vADDRESS2 = N'" . (isset($address['street'][1]) ? $address['street'][1] : " ") . "',
		@I_vCNTCPRSN = N'" . $order['customer_firstname'] . " " . $order['customer_lastname'] . "',
		@I_vCITY = N'" . $address['city'] . "',
		@I_vSTATE = N'" . $address['region_id'] . "',
		@I_vZIPCODE = N'" . $address['postcode'] . "',
		@I_vCOUNTRY = N'" . $address['country_id'] . "',
		@I_vPHNUMBR1 = N'" . $address['telephone'] . "',
		@I_vSUBTOTAL = " . $order['base_subtotal_incl_tax'] . ",
		@I_vDOCAMNT = " . $order['grand_total'] . ",
		@I_vPYMTRCVD = " . $order['subtotal_incl_tax'] . ",
		@I_vBACHNUMB = 0,
		@O_iErrorState = @O_iErrorState OUTPUT,
		@oErrString = @oErrString OUTPUT

		SELECT	@O_iErrorState as N'@O_iErrorState',
				@oErrString as N'@oErrString';

		SELECT	'Return Value' = @return_value;";

		$response = $model->getData($query);
		if (isset($response['ERROR'])) {
			$this->helper('Log')->log($query);
			return false;
		} else {
			return true;
		}
	}

	private function orderLine($order) {
		$model = $this->getModel('Default');
		$response = [];
		foreach ($order['items'] as $item) {
			if (floatval($item['price']) > 0) {
				$tax = $item['price_incl_tax'] - $item['price'];
				$query = "
				DECLARE	@return_value int,
					@O_iErrorState int,
					@oErrString varchar(255);

				EXEC	@return_value = taSopLineIvcInsert
						@I_vSOPTYPE = 2,
						@I_vSOPNUMBE = N'" . $order['SOPNUMBER'] . "',
						@I_vCUSTNMBR = N'" . $order['CUSTNMBR'] . "',
						@I_vDOCDATE = N'" . $order['created_at'] . "',
						@I_vITEMNMBR = N'" . $item['sku'] . "',
						@I_vUNITPRCE = " . $item['price'] . ",
						@I_vXTNDPRCE = " . $item['price'] . ",
						@I_vQUANTITY = " . $item['qty_ordered'] . ",
						@I_vTAXAMNT = " . $tax . ",
						@O_iErrorState = @O_iErrorState OUTPUT,
						@oErrString = @oErrString OUTPUT;

				SELECT	@O_iErrorState as N'@O_iErrorState',
						@oErrString as N'@oErrString';

				SELECT	'Return Value' = @return_value;
				";
				$_response = $model->getData($query);
				if (isset($_response['ERROR'])) {
					$this->helper('Log')->log($query);
					$response[] = $_response;
				} else {
					$response[] = true;
				}
			}
		}
		return $response;
	}

	public function orders () {
		$this->helper('Log')->log('SYNC :: Orders ================ >>');
		$orders = $this->helper('ApiConsumer')->getOrders();
		$next = true;
		foreach ($orders as $order) {
			if ($next) {
				$gpId = $this->getNextId();
			}
			$this->helper('Log')->log('Register order ' . $order['increment_id'] . ' as ' . $gpId);
			$order['CUSTNMBR'] = $this->getCustomerId($order['customer_id']);
			//if ($order['CUSTNMBR']) {
				$order['SOPNUMBER'] = trim($gpId);
				//if (
					$this->orderLine($order);//) {
					$this->orderHdr($order);
					$next = true;
				// } else {
				// 	$next = false;
				// 	echo '[ERROR] when Register order ' . $order['increment_id'] . ' as ' . $gpId . PHP_EOL;
				// }
			// } else {
			// 	$next = false;
			// 		echo '[ERROR] when Register customer ' . $order['customer_email'] . ' for order ' . $order['increment_id'] . PHP_EOL;
			// }
		}

		$model = $this->getModel('Default');
		
		$this->helper('Log')->log('<< =========== END Orders SYNC');
	}

}