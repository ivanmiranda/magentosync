<?php

use Sincco\Sfphp\Config\Reader;

class SalesCommand extends Sincco\Sfphp\Abstracts\Command {

	private function isOrderCreated($order) {
		$model = $this->getModel('Default');
		$query = "SELECT SOPNUMBE, ORIGNUMB FROM SOP10100 WHERE ORIGNUMB='" . $order['increment_id'] . "' AND DOCID='WEB';"; // AND CUSTNMBR='" . $order['CUSTNMBR'] . "';";
		$data = $model->getData($query);
		if (count($data) > 0) {
			return true;
		} else {
			return false;
		}
	}

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
		$query = "SELECT * FROM CA_vw_Magento_CustomerInformation WHERE CUSTNMBR='TST" . $id . "';";
		$_customerData = $model->getData($query);
		if (count($_customerData) > 0) {
			return "TST" . $id; //$_customerData['CUSTNMBR'];
		} else {
			$address = $customer['addresses'][0];
			$query = "
				DECLARE	@return_value int,
						@O_iErrorState int,
						@oErrString varchar(255)

				EXEC	@return_value = taUpdateCreateCustomerRcd
						@I_vCUSTNMBR = N'TST" . $customer['id'] . "',
						@I_vCUSTNAME = N'" . $customer['firstname'] . " " . $customer['lastname'] . "',
						@I_vADRSCODE='PRIMARY',
						@I_vSHRTNAME = N'" . $customer['email'] . "',
						@I_vADDRESS1 = N'" . $address['street'][0] . "',
						@I_vADDRESS2 = N'" . (isset($address['street'][1]) ? $address['street'][1] : " ") . "',
						@I_vADDRESS3 = N'" . (isset($address['street'][2]) ? $address['street'][2] : " ") . "',
						@I_vCITY = N'" . $address['city'][0] . "',
						@I_vSTATE = N'" . $address['region']['region'] . "',
						@I_vZIPCODE = N'" . $address['postcode'] . "',
						@I_vCCode = N'" . $address['country_id'] . "',
						@I_vPHNUMBR1 = N'" . $address['telephone'] . "',
						@I_vCRLMTTYP = 1,
						@I_vTAXSCHID = 'AVATAX',

						@O_iErrorState = @O_iErrorState OUTPUT,
						@oErrString = @oErrString OUTPUT

				SELECT	@O_iErrorState as N'@O_iErrorState',
						@oErrString as N'@oErrString'

				SELECT	'Return Value' = @return_value;";
			// $this->helper('Log')->log($query);
			$response = $model->getData($query);
			$query = "SELECT * FROM CA_vw_Magento_CustomerInformation WHERE CUSTNMBR='TST" . $id . "';";
			$_customerData = $model->getData($query);
			if (count($_customerData) > 0) {
				return "TST" . $id; //$_customerData['CUSTNMBR'];
			} else {
				return false;
			}
		}
	}

	private function orderHdr($order, $batchid) {
		$model = $this->getModel('Default');
		$address = $order['extension_attributes']['shipping_assignments'][0]['shipping']['address'];
		$order['created_at'] = explode(" ", $order['created_at']);
		$order['created_at'] = $order['created_at'][0];

		$query = "
		DECLARE	@return_value int,
		@O_iErrorState int,
		@oErrString varchar(255);  		
		EXEC	@return_value = taSopHdrIvcInsert 
				@I_vSOPTYPE = 2,
				@I_vDOCID = N'WEB',
				@I_vSOPNUMBE = N'" . $order['SOPNUMBER'] . "',
				@I_vORIGNUMB = N'" . $order['increment_id'] . "',
				@I_vTAXAMNT = " . $order['base_tax_amount'] . ",
				@I_vFREIGHT = " . $order['base_shipping_amount'] . ",
				@I_vLOCNCODE = N'HH',
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
				@I_vSUBTOTAL = " . $order['base_subtotal'] . ",
				@I_vDOCAMNT = " . $order['total_due'] . ",
				@I_vBACHNUMB = 'MGT" . $batchid . "',
				@I_vTAXSCHID = 'AVATAX',
				@O_iErrorState = @O_iErrorState OUTPUT,
				@oErrString = @oErrString OUTPUT ;

		SELECT	@O_iErrorState as N'@O_iErrorState',
				@oErrString as N'@oErrString'; 
		SELECT	'Return Value' = @return_value;";

		$response = $model->getData($query);
		return preg_replace('/\s+/S', " ",$query);
		// $this->helper('Log')->log($query);
	}

	private function orderLine($order) {
		$model = $this->getModel('Default');
		$response = [];
		foreach ($order['items'] as $item) {
			if (floatval($item['price']) > 0) {
				$UOFM = $model->getData("SELECT UOMSCHDL FROM CA_vw_Magento_InventoryItems WHERE ITEMNMBR = '" . $item['sku'] . "' AND LOCNCODE='HH';");
				$UOFM = array_pop($UOFM);
				$order['created_at'] = explode(" ", $order['created_at']);
				$order['created_at'] = $order['created_at'][0];
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
						@I_vUNITPRCE = " . $item['base_price'] . ",
						@I_vXTNDPRCE = " . $item['row_total'] . ",
						@I_vQUANTITY = " . $item['qty_ordered'] . ",
						@I_vReqShipDate = N'" . $order['created_at'] . "',
						@I_vFUFILDAT = N'1900-01-01',
						@I_vACTLSHIP = N'1900-01-01',
						@I_vLOCNCODE = 'HH',
						@I_vUOFM = '" . $UOFM['UOMSCHDL'] . "',
						@O_iErrorState = @O_iErrorState OUTPUT,
						@oErrString = @oErrString OUTPUT;

				SELECT	@O_iErrorState as N'@O_iErrorState',
						@oErrString as N'@oErrString';

				SELECT	'Return Value' = @return_value;";
				$_response = $model->getData($query);
				$response[] = preg_replace('/\s+/S', " ",$query);
				// $this->helper('Log')->log($query);
			}
		}
		return $response;
	}

	public function orders () {
		$model = $this->getModel('Default');
		$this->helper('Log')->log('SYNC :: Orders ================ >>');
		$orders = $this->helper('ApiConsumer')->getOrders();
		$next = true;
		$batchid = 'MGT' . date('Ymd');
		foreach ($orders as $order) {
			$order['CUSTNMBR'] = $this->getCustomerId($order['customer_id']);
			$order['increment_id'] = 'L' . $order['increment_id'];
			if (!$this->isOrderCreated($order)) {
				if ($next) {
					$gpId = $this->getNextId();
				}
				$order['SOPNUMBER'] = trim($gpId);
				// $this->helper('Log')->log('Register order ' . $order['increment_id'] . ' as ' . $gpId . " " . $order['CUSTNMBR']);

				if ($order['CUSTNMBR']) {
					$_queryError = [];
					$_queryError['lines'] = $this->orderLine($order);//) {
					$_queryError['hdr'] = $this->orderHdr($order, $batchid);
					$query = "SELECT SOPNUMBE, ORIGNUMB FROM SOP10100 WHERE ORIGNUMB='" . $order['increment_id'] . "' AND DOCID='WEB';"; // AND CUSTNMBR='" . $order['CUSTNMBR'] . "';";
					$data = $model->getData($query);
					if (count($data) > 0) {
						$this->helper('Log')->log('Register order ' . $order['increment_id'] . ' as ' . $gpId . " " . $order['CUSTNMBR']);
						$next = true;
					} else {
						$this->helper('Log')->log('[ERROR]', $_queryError);
						echo '[ERROR] when Register order '. $order['increment_id'] . PHP_EOL;
						die();
					}
				} else {
				 	$next = false;
				 	echo '[ERROR] when Register customer ' . $order['customer_email'] . ' for order ' . $order['increment_id'] . PHP_EOL;
				}
			} else {
				$this->helper('Log')->log('Order ' . $order['increment_id'] . ' already created');
			}
			// die();
		}

		$model = $this->getModel('Default');
		
		$this->helper('Log')->log('<< =========== END Orders SYNC');
	}

}

/*
DECLARE @return_value int,
	 @O_iErrorState int,
	 @oErrString varchar(255);
EXEC @return_value = taSopLineIvcInsert @I_vSOPTYPE = 2,
	 @I_vSOPNUMBE = N'ORD0362588',
	 @I_vCUSTNMBR = N'TST7373',
	 @I_vDOCDATE = N'2018-08-25',
	 @I_vITEMNMBR = N'9622-00',
	 @I_vUNITPRCE = 24.52,
	 @I_vXTNDPRCE = 49.04,
	 @I_vTAXAMNT = 4.35,
	 @I_vQUANTITY = 2,
	 @I_vReqShipDate = N'2018-08-25',
	 @I_vFUFILDAT = N'1900-01-01',
	 @I_vACTLSHIP = N'1900-01-01',
	 @I_vLOCNCODE = 'HH',
	 @I_vUOFM = 'EA ',
	 @O_iErrorState = @O_iErrorState OUTPUT,
	 @oErrString = @oErrString OUTPUT;
SELECT @O_iErrorState as N'@O_iErrorState',
	 @oErrString as N'@oErrString';
SELECT 'Return Value' = @return_value;

DECLARE @return_value int,
	 @O_iErrorState int,
	 @oErrString varchar(255);
EXEC @return_value = taSopLineIvcInsert @I_vSOPTYPE = 2,
	 @I_vSOPNUMBE = N'ORD0362588',
	 @I_vCUSTNMBR = N'TST7373',
	 @I_vDOCDATE = N'2018-08-25',
	 @I_vITEMNMBR = N'6402-BB',
	 @I_vUNITPRCE = 0.01,
	 @I_vXTNDPRCE = 0.01,
	 @I_vTAXAMNT = 0,
	 @I_vQUANTITY = 1,
	 @I_vReqShipDate = N'2018-08-25',
	 @I_vFUFILDAT = N'1900-01-01',
	 @I_vACTLSHIP = N'1900-01-01',
	 @I_vLOCNCODE = 'HH',
	 @I_vUOFM = 'EA ',
	 @O_iErrorState = @O_iErrorState OUTPUT,
	 @oErrString = @oErrString OUTPUT;
SELECT @O_iErrorState as N'@O_iErrorState',
	 @oErrString as N'@oErrString';
SELECT 'Return Value' = @return_value;

DECLARE @return_value int,
	 @O_iErrorState int,
	 @oErrString varchar(255);
EXEC @return_value = taSopLineIvcInsert @I_vSOPTYPE = 2,
	 @I_vSOPNUMBE = N'ORD0362588',
	 @I_vCUSTNMBR = N'TST7373',
	 @I_vDOCDATE = N'2018-08-25',
	 @I_vITEMNMBR = N'2062-5R',
	 @I_vUNITPRCE = 11.25,
	 @I_vXTNDPRCE = 11.25,
	 @I_vTAXAMNT = 1,
	 @I_vQUANTITY = 1,
	 @I_vReqShipDate = N'2018-08-25',
	 @I_vFUFILDAT = N'1900-01-01',
	 @I_vACTLSHIP = N'1900-01-01',
	 @I_vLOCNCODE = 'HH',
	 @I_vUOFM = 'EA ',
	 @O_iErrorState = @O_iErrorState OUTPUT,
	 @oErrString = @oErrString OUTPUT;
SELECT @O_iErrorState as N'@O_iErrorState',
	 @oErrString as N'@oErrString';
SELECT 'Return Value' = @return_value;

DECLARE @return_value int,
	 @O_iErrorState int,
	 @oErrString varchar(255);
EXEC @return_value = taSopHdrIvcInsert @I_vSOPTYPE = 2,
	 @I_vDOCID = N'WEB',
	 @I_vSOPNUMBE = N'ORD0362588',
	 @I_vORIGNUMB = N'L000000002',
	 @I_vTAXAMNT = 5.35,
	 @I_vFREIGHT = 0,
	 @I_vLOCNCODE = N'HH',
	 @I_vDOCDATE = '2018-08-25',
	 @I_vCUSTNMBR = 'TST7373',
	 @I_vCUSTNAME = N'Ignacio Pascual',
	 @I_vCSTPONBR = N'L000000002',
	 @I_vShipToName = N'Ignacio Pascual',
	 @I_vADDRESS1 = N'21 Maiden Ln',
	 @I_vADDRESS2 = N' ',
	 @I_vCNTCPRSN = N'Ignacio Pascual',
	 @I_vCITY = N'New York',
	 @I_vSTATE = N'43',
	 @I_vZIPCODE = N'10038-4088',
	 @I_vCOUNTRY = N'US',
	 @I_vPHNUMBR1 = N'222-456-7890',
	 @I_vSUBTOTAL = 60.3,
	 @I_vDOCAMNT = 65.65,
	 @I_vBACHNUMB = 'MGTMGT20180906',
	 @I_vTAXSCHID = 'AVATAX',
	 @I_vUSINGHEADERLEVELTAXES = 1,
	 @I_vCREATETAXES = 0,
	 @O_iErrorState = @O_iErrorState OUTPUT,
	 @oErrString = @oErrString OUTPUT ;
SELECT @O_iErrorState as N'@O_iErrorState',
	 @oErrString as N'@oErrString';
SELECT 'Return Value' = @return_value;

DECLARE @return_value int,
	 @O_iErrorState int,
	 @oErrString varchar(255);
EXEC @return_value = taSopLineIvcTaxInsert
	@I_vSOPTYPE = 2,
	 @I_vSOPNUMBE = N'ORD0362588',
	 @I_vCUSTNMBR = 'TST7373',
	 @I_vSALESAMT = 65.65,
	 @I_vTAXDTLID = 'AVATAX',
	 @I_vSTAXAMNT = 5.35,
	 @O_iErrorState = @O_iErrorState OUTPUT,
	 @oErrString = @oErrString OUTPUT ;
SELECT @O_iErrorState as N'@O_iErrorState',
	 @oErrString as N'@oErrString';
SELECT 'Return Value' = @return_value;