<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda
# @version: 2.0.0
# -----------------------
# Create a new PDO connection
# -----------------------

namespace Sincco\Sfphp\DB;

use Sincco\Tools\Debug;

class Connector extends \PDO {

	public function __construct($connectionData) {
		try{
			//$dbh= new PDO('odbc:dynamics', 'ipascualTEST', 'p4t1t0l0c0');
			parent::__construct('odbc:dynamics', 'ipascualTEST', 'p4t1t0l0c0');
			$this->setAttribute(self::ATTR_ERRMODE, self::ERRMODE_EXCEPTION);
			$this->setAttribute(self::ATTR_EMULATE_PREPARES, false);
			$stmt = $this->prepare("use TEST");
			$stmt->execute();
		} catch (\PDOException $err) {
			$errorInfo = sprintf('%s: %s in %s on line %s.',
				'Database Error',
				$err,
				$err->getFile(),
				$err->getLine()
			);
			throw new \Exception($errorInfo, 0);
		}
	}
}