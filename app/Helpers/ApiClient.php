<?php 

use Desarrolla2\Cache\Cache;
use Desarrolla2\Cache\Adapter\File;

final class ApiClientHelper extends \Sincco\Sfphp\Abstracts\Helper {

	public static function client() {
		return new \GuzzleHttp\Client(['base_uri'=>'https://integration-5ojmyuq-g46zmjprelses.us-3.magentosite.cloud/rest/V1/']);
	}

	public static function getToken() {
		try {
			$client = self::client();
			$response = $client->request('POST', 'integration/admin/token', ['json'=>['username'=>'apigp', 'password'=>'p4t1t0l0c0#']]);
			if ($response->getStatusCode() == 200) {
				return str_replace('"', '', $response->getBody());
			} else {
				return false;
			}
 		} catch(\GuzzleHttp\Exception\ClientException $e) {
 			return false;
 		}
	}

 	public static function authenticate() {
 		$adapter = new File(PATH_CACHE);
		$adapter->setOption('ttl', 18000);
		$cache = new Cache($adapter);
		if(is_null($cache->get('token'))) {
			$token = self::getToken();
			$cache->set('token', $token, 18000);
		} else {
			$token = $cache->get('token');
		}
		return $token;
	}
}