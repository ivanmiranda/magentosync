<?php 

use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

final class ApiConsumerHelper extends \Sincco\Sfphp\Abstracts\Helper {

	public static function getLastSync($section) {
		$lastSync = [];
		if (file_exists(PATH_ROOT . '/var/data/last_sync.json')) {
			$lastSync = json_decode(file_get_contents(PATH_ROOT . '/var/data/last_sync.json'));
		}
		if (isset($lastSync[$section])) {
			$lastSync = $lastSync[$section];
		} else {
			$lastSync = '2018-01-01 00:00:01';
		}
		return $lastSync;
	}

	public static function updStock($sku, $qty) {
		try {
			$token = self::helper('ApiClient')->authenticate();
			$client = self::helper('ApiClient')->client();
			$response = $client->request('PUT', 'products/' . $sku . '/stockItems/1', ['headers'=>['Authorization'=>'Bearer ' . $token, 'Content-Type'=>'application/json'], 'json'=>['stockItem'=>['qty'=>$qty]]]);
			if ($response->getStatusCode() == 200) {
				return $response->getBody();
			} else {
				return $response->getStatusCode();
			}
		} catch(\GuzzleHttp\Exception\RequestException $e) {
 			return false;
 		}
	}

	public static function getOrders() {
		$lastSync = self::getLastSync('orders');

		$searchCriteria = 
			"searchCriteria[filter_groups][0][filters][0][field]=created_at&" .
			"searchCriteria[filter_groups][0][filters][0][value]=" . $lastSync . "&" .
			"searchCriteria[filter_groups][0][filters][0][condition_type]=from";
		// self::helper('Log')->log('orders?' . urlencode($searchCriteria)); die();

		try {
			$token = self::helper('ApiClient')->authenticate();
			$client = self::helper('ApiClient')->client();
			$response = $client->request('GET', 'orders?' . $searchCriteria,  ['headers'=>['Authorization'=>'Bearer ' . $token, 'Content-Type'=>'application/json']]);
			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);
				// self::helper('Log')->log('orders ',$response['items']);die();
				if (isset($response['items'])) {
					$response = $response['items'];
				} else {
					$response = [];
				}
				return $response;
			} else {
				return false;
			}
		} catch(\GuzzleHttp\Exception\ClientException $e) {
 			return false;
 		}
	}

	public static function getCustomer($id) {
		try {
			$token = self::helper('ApiClient')->authenticate();
			$client = self::helper('ApiClient')->client();
			$response = $client->request('GET', 'customers/' . $id,  ['headers'=>['Authorization'=>'Bearer ' . $token, 'Content-Type'=>'application/json']]);
			if ($response->getStatusCode() == 200) {
				$response = json_decode($response->getBody(), true);
				return $response;
			} else {
				return false;
			}
		} catch(\GuzzleHttp\Exception\ClientException $e) {
 			return false;
 		}
	}
}