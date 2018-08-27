<?php
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

final class LogHelper extends \Sincco\Sfphp\Abstracts\Helper {

	public static function log($message, $params = []) {
		$firephp = new FirePHPHandler();
		$log = new Monolog('sync');
		$log->pushHandler(new StreamHandler(PATH_LOGS . '/sync.log', Monolog::DEBUG));
		$log->pushHandler($firephp);
		$log->debug($message, $params);
	}

}