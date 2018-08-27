<?php
# NOTICE OF LICENSE
#
# This source file is subject to the Open Software License (OSL 3.0)
# that is available through the world-wide-web at this URL:
# http://opensource.org/licenses/osl-3.0.php
#
# -----------------------
# @author: Iván Miranda (@deivanmiranda)
# @version: 2.0.0
# -----------------------

namespace Sincco\Sfphp;

use Sincco\Sfphp\ClassLoader;
use Sincco\Sfphp\Crypt;
use Sincco\Sfphp\DB\DataManager;
use Sincco\Sfphp\Config\Writer;
use Sincco\Sfphp\Config\Reader;
use Sincco\Tools\Singleton;

use League\CLImate\CLImate;
use Composer\Factory;
use Composer\Console\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\ArrayInput;

final class Console extends \stdClass {

	public function run($argv) {
		Paths::init();
		Reader::get('app');
		Translations::init();

		array_shift($argv);
		$arguments = $argv;
		$command = array_shift($arguments);

		// It's a petition for an URL registered on system
		$_SERVER['SERVER_SOFTWARE'] = '';
		$_SERVER['REQUEST_METHOD'] = 'cli';
		$_config = Reader::get('app');
		if(isset($_config['timezone'])) {
			date_default_timezone_set($_config['timezone']);
		}
		
		$params = [];
		foreach ($arguments as $_param) {
			$param = explode('=', $_param);
			$params[$param[0]] = $param[1];
		}

		// Internal Functions
		if(is_callable(array($this,str_replace(':', '_', $command)))) {
			$params = [];
			$ref = new \ReflectionMethod($this,str_replace(':', '_', $command));
			$int = 0;
			foreach ($ref->getParameters() as $key => $value) {
				$int++;
			 	$cli = new CLImate;
			 	$input = $cli->input($value->name . '?');
			 	$params[]= $input->prompt();
			}
			call_user_func_array(array($this,str_replace(':', '_', $command)), $params);
		}
		else {
			$command = explode(':', ucfirst($command));
			$objClass = ClassLoader::load([], $command[0], 'Command');
			if(is_callable(array($objClass, $command[1]))) {
				call_user_func_array(array($objClass, $command[1]), $params);
			}
			else {
				throw new \Exception('No es posible lanzar ' . $command[0] . '->' . $command[1], 0);
			}
		}
	}

	public function help_() {
		$commands = get_class_methods($this);
		$data = [];
		foreach ($commands as $command) {
			$command = str_replace('help_', 'help', $command);
			$command = explode('_', $command);
			if (count($command) > 1){
				$data[] = ['Comandos'=>implode(':', $command)];
			}
		}
		$cli = new CLImate;
		$cli->table($data);
	}

	// ------------------------------
	// ------------------ APP section
	// ------------------------------

	/**
	 * Defines main configuration app
	 */
	public function app_init($nombreApp, $cliente, $url, $force_yN = 'N') {
		$climate = new CLImate;
		$_SERVER['SERVER_NAME'] = NULL;
		$_SERVER['REQUEST_URI'] = NULL;
		$config = Reader::get();
		if (count($config)) {
			if (strtolower(trim($force_yN)) == 'y') {
				$this->cache_clean();
			}
			else {
				$climate->red('Ya existe una configuración, si desea reiniciar la APP especifique la opción "force"');
			}
		}
		$climate->green('Reiniciando configuración de aplicación...');
		$_llave_encripcion = strtoupper(md5(microtime().rand()));
		$bases = [];
		$_config = [
			'app' => [
				'key' => $_llave_encripcion,
				'name' => $nombreApp,
				'company' => $cliente,
				'timezone' => 'America/Mexico_City',
			],
			'front' => [
				'url' => $url,
			],
			'bases' => $bases,
			'sesion' => [
				'type' => 'DEFAULT',
				'name' => str_replace(' ', '', strtolower($nombreApp)),
				'ssl' => 0,
				'inactivity' => 300,
			],
			'dev' => [
				'showerrors' => 1,
			],
		];
		if (!defined('APP_KEY')) {
			define('APP_KEY', $_llave_encripcion);
		}

		$fileSystem = new Filesystem();
		if (!$fileSystem->exists('etc/config')) {
			$fileSystem->mkdir('etc/config/', 0750);
		}

		Writer::write($_config, 'config', 'etc/config/config.xml');
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('Archivo de configuración inicializado');
	}

	/**
	 * Adds new connection data for a DB
	 * @return none           
	 */
	public function app_db($identificador, $tipo, $host, $user, $dbname, $password){
		$fileSystem = new Filesystem();
		$climate = new CLImate;
		$climate->green('Registrando base de datos...');
		$config = Reader::get();
		$config['bases'][$identificador]['type'] = $tipo;
		$config['bases'][$identificador]['host'] = $host;
		$config['bases'][$identificador]['user'] = $user;
		$config['bases'][$identificador]['dbname'] = $dbname;
		$config['bases'][$identificador]['password'] = Crypt::encrypt($password);
		Writer::write($config, 'config', 'etc/config/config.xml');
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('OK');
	}

	/**
	 * Apply update for app
	 * @return none
	 */
	public function app_update() {
		$climate = new CLImate;
		$progress = $climate->progress()->total(2);
		$progress->advance(1, 'Actualizando dependencias...');
		$app = new Application();
		$factory = new Factory();
		$output = $factory->createOutput();
		$input = new ArrayInput(array(
		  'command' => 'update',
		));
		$input->setInteractive(false);
		$cmdret = $app->doRun($input,$output);		
		$progress->advance(1, 'Hecho');
		$climate->lightGreen('Ok');
	}

	// ------------------------------
	// ------------------- DB section
	// ------------------------------

	/**
	 * Create an backup script for a DB
	 * @param  string $dbId
	 * @return none      
	 */
	public function db_backup($dbId) {
		$climate = new CLImate;
		if (trim($dbId) == '') {
			$dbId = 'default';
		}
		$data = Reader::get('bases');
		$data = $data[$dbId];
		$data['password'] = trim(Crypt::decrypt($data['password']));
		$db = new DataManager($data);
		
		echo 'Respaldando información...' . PHP_EOL;

		if($data['type'] == 'firebird') {
			$data['dbname'] = $dbId;
			$query = 'select rdb$relation_name AS Tables_in_' . $data['dbname'] .
				' from rdb$relations where rdb$view_blr is null and (rdb$system_flag is null or rdb$system_flag = 0);';
		} else {
			$query = 'SHOW TABLES';
		}
		$tables = $db->query($query);

		$fileData = NULL;

		foreach($tables as $table) {
			if($data['type'] == 'firebird')
				$table = $table['TABLES_IN_' . strtoupper($data['dbname'])];
			else
				$table = $table['Tables_in_' . $data['dbname']];

			$tableData = $db->query('SELECT * FROM '.$table);
			
			if($data['type'] != 'firebird') {
				$fileData.= "-- ----------------------------------------------\n" .
					"DROP TABLE IF EXISTS `" . $table . "`;";
				$create = $db->query('SHOW CREATE TABLE ' . $table);
				$fileData.= "\n\n" . str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS ', $create[0]['Create Table']) . ";\n\n";
			} else {
				$fileData.= "-- ----------------------------------------------\n";
			}

			if(count($tableData) > 0) {
				$fileData .= 'INSERT INTO `' . $table . '` (' .
					implode(',', array_keys($tableData[0])) . ") VALUES \n";
				$dump = NULL;
				foreach($tableData as $register) {
					$dump[] = "('" . implode("','", array_values($register)) . "')";
				}
				$fileData .= implode(",\n", $dump) . ';';
				
			}
			$fileData.="\n\n";
		}

		$file = $dbId . '-backup-' . date("ymdGis") . '.sql';
		$handle = fopen('./bkp/' . $file ,'w+');
		fwrite($handle,$fileData);
		fclose($handle);
		echo 'Archivo ' . $file . ' creado' . PHP_EOL;
	}

	/**
	 * Restores an SQL backup script on a DB
	 * @param  string $dbId 
	 * @param  string $file 
	 * @return none       
	 */
	public function db_restore($dbId, $file) {
		echo 'Recuperando base de datos...' . PHP_EOL;
		$dbData = Reader::get('bases');
		$dbData = $dbData[$dbId];
		$dbData['password'] = trim(Crypt::decrypt($dbData['password']));
		$db = new DataManager($dbData);

		$lines = file($file);
		$query = '';
		foreach ($lines as $line) {
			if (substr($line, 0, 2) == '--' || $line == '')
			continue;
			$query .= $line;
			if (substr(trim($line), -1, 1) == ';') {
				$db->query($query);
				$query = '';
			}
		}
		echo 'Terminado' . PHP_EOL;
	}

	/**
	 * Updates the principal DB with updates scripts
	 * @return none
	 */
	public function db_update() {
		$data = Reader::get('bases');
		$data = $data['default'];
		$data['password'] = trim(Crypt::decrypt($data['password']));
		$db = new DataManager($data);
		echo 'Buscando actualizaciones disponibles...' . PHP_EOL;
		foreach (glob('*.upd.sql') as $update) {
			echo '--' . $update . PHP_EOL;
			$lines = file($update);
			$query = '';
			foreach ($lines as $line) {
				if (substr($line, 0, 2) == '--' || $line == '')
				continue;
				$query .= $line;
				if (substr(trim($line), -1, 1) == ';') {
					$db->query($query);
					$query = '';
				}
			}
			rename($update, './bkp/' . $update);
		}
		echo 'Actualizaciones aplicadas' . PHP_EOL;
	}

	// ------------------------------
	// ---------------- CACHE section
	// ------------------------------

	/**
	 * Clean app cache
	 * @return none
	 */
	public function cache_clean($file = PATH_CACHE) {
		$climate = new CLImate;
		$climate->green('Limpiando cache...');
		$files = glob($file . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->cache_clean($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
		$climate->lightGreen('Ok');
	}

	public function cache_off() {
		$climate = new CLImate;
		$climate->green('Desactivando cache...');
		$config = Reader::get();
		$config['dev']['cache'] = 0;
		Writer::write($config, 'config', 'etc/config/config.xml');
		$fileSystem = new Filesystem();
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('Ok');
	}

	public function cache_on() {
		$climate = new CLImate;
		$climate->green('Activando cache...');
		$config = Reader::get();
		$config['dev']['cache'] = 1;
		Writer::write($config, 'config', 'etc/config/config.xml');
		$fileSystem = new Filesystem();
		$fileSystem->chmod('etc/config/config.xml', 0750);
		$climate->lightGreen('Ok');
	}

	// ------------------------------
	// -------------- SESSION section
	// ------------------------------

	/**
	 * Terminate app sessions
	 * @return none
	 */
	public function session_clean($file = PATH_SESSION) {
		echo 'Cerrando sesiones...' . PHP_EOL;
		$files = glob($file . '/*'); 
		foreach($files as $file){
			if (is_dir($file) and !in_array($file, array('..', '.')))  {
				$this->session_clean($file);
				rmdir($file);
			} else if(is_file($file) and ($file != __FILE__)) {
				unlink($file); 
			}
		}
		echo 'Sesiones cerradas' . PHP_EOL;
	}

}