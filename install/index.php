<?php
error_reporting(E_ALL);
define('SYSTEM_ROOT', realpath(dirname(__FILE__) . '/../'));

if(defined('SERVER_TIMEZONE')) {
	date_default_timezone_set(SERVER_TIMEZONE);	
} else {
	$timezone = ini_get('date.timezone');
	
	if(!empty($timezone)) {
		date_default_timezone_set($timezone);
	} else {
		date_default_timezone_set('UTC');	
	}
}

require_once SYSTEM_ROOT . '/classes/smarty/Smarty.class.php';
require_once SYSTEM_ROOT . '/classes/AutoloadHelper.class.php';
require_once SYSTEM_ROOT . '/install/ConfigurationChecks.class.php';

if(file_exists(SYSTEM_ROOT . '/system/config.php')) {
	require_once SYSTEM_ROOT . '/system/config.php';	
}

$autoload = AutoloadHelper::getInstance();
$autoload->addPattern('Form.%s.class.php');
$autoload->addDirectory(
	SYSTEM_ROOT . '/classes',
	SYSTEM_ROOT . '/classes/form',
	SYSTEM_ROOT . '/model'
);
spl_autoload_register(array($autoload, 'invoke'));

if(!is_writable(SYSTEM_ROOT . '/classes/smarty/templates_c/')) {
	die('<b>Fatal error: </p> <code>' . SYSTEM_ROOT . '/classes/smarty/templates_c/</code> is not writable. Please make this directory writable');
}

final class Install {
	public static function run($action) {
		ConfigurationChecks::loadChecks();
		ConfigurationChecks::performChecks();
		
		$smarty = new Smarty();
        
		$smarty->muteExpectedErrors();
		
		$smarty->setCacheDir(SYSTEM_ROOT . '/classes/smarty/cache/');
		$smarty->setCompileDir(SYSTEM_ROOT . '/classes/smarty/templates_c/');
		$smarty->setTemplateDir(SYSTEM_ROOT . '/install/view/');
		
        $smarty->caching = Smarty::CACHING_OFF;
        $smarty->force_compile = true;
        
		$smarty->assign('title', 'Installation');
		
		switch($action) {
			case 'tables':
				self::insertTables($smarty);
				break;
			
			case 'user':
				self::createUser($smarty);
				break;
			
			case 'success':
				self::success($smarty);
				break;
			
			default:
				self::checkRequirements($smarty);
				break;
		}
	}
	
	public static function checkRequirements(Smarty $smarty) {		
		$smarty->assign('heading', 'Configuration check');
        $smarty->assign('checks', ConfigurationChecks::$checks);		
		$smarty->assign('extensions', ConfigurationChecks::$extensions);
		
		$smarty->assign('curStep', 1);
		$smarty->assign('canProceed', ConfigurationChecks::getResult() == CheckResult::OK);
		
		$smarty->display('check.tpl');
	}
	
	public static function insertTables(Smarty $smarty) {
		if(ConfigurationChecks::getResult() != CheckResult::OK) {
			header('Location: index.php');
			exit;	
		}
		
		$sql = file_get_contents(SYSTEM_ROOT . '/install/install.sql');
		
		try {
			$db = new Database('mysql:dbname='.DATABASE_NAME.';host='.DATABASE_HOST, DATABASE_USER, DATABASE_PASS);
			
			$sql = $db->prepare('SHOW TABLES');
			$sql->execute();
			
			if($sql->rowCount() == 0 || Utils::getGET('confirm', false) != false) {
				$insert = file_get_contents('./install.sql');
				
				$db->exec($insert);
				
				header('Location: index.php?action=user');
				exit;
			} else {
				$smarty->assign('heading', 'Database tables');
				$smarty->assign('curStep', 2);
				
				$smarty->display('confirm.tpl');
			}
		} catch(PDOException $e) {
			$smarty->assign('heading', 'Database tables');
			$smarty->assign('error', $e->getMessage());
			$smarty->assign('url', 'index.php?action=tables&#38;confirm=yes');
			$smarty->assign('curStep', 2);
			
			$smarty->display('error.tpl');
		}
	}
	
	public static function createUser(Smarty $smarty) {
		if(ConfigurationChecks::getResult() != CheckResult::OK) {
			header('Location: index.php');
			exit;	
		}
		
		try {
			$db = new Database('mysql:dbname='.DATABASE_NAME.';host='.DATABASE_HOST, DATABASE_USER, DATABASE_PASS);
			
			// Test if all tables where created properly
			$sql = $db->prepare('SHOW TABLES');
			$sql->execute();
			
			if($sql->rowCount() == 0) {
				header('Location: index.php?action=tables');
				exit;	
			}
			
			$errorUsername = '';
			$errorPassword = '';
			
			$username = Utils::getPOST('username', '');
			$password = Utils::getPOST('password', '');
			
			if(Utils::getPOST('submit', false) != false) {
				if(empty($username)) {
					$errorUsername = 'Username must not be empty.';
				} else if(empty($password)) {
					$errorPassword = 'Password must not be empty.';
				} else {
				
					$sql = $db->prepare('INSERT INTO users (username, password, salt, last_login, lang, admin) VALUES (:username, :password, :salt, :lastlogin, :language, :admin)');
					
					$salt = Utils::createPasswordSalt();
					$sql->execute(array(
						':username' => $username,
						':password' => Utils::createPasswordHash($password, $salt),
						':salt'		=> $salt,
						':lastlogin'	=> time(),
						':admin' => '1',
						':language'	=> LANGUAGE
					));
					unset($salt);
					
					header('Location: index.php?action=success');
					exit;
				}
			}
			
			$smarty->assign('heading', 'Create user account');
			$smarty->assign('username', $username);
			$smarty->assign('errorUsername', $errorUsername);
			$smarty->assign('errorPassword', $errorPassword);
			$smarty->assign('curStep', 3);
			
			$smarty->display('form.tpl');
		} catch(PDOException $e) {
			$smarty->assign('heading', 'Database tables');
			$smarty->assign('error', $e->getMessage());
			$smarty->assign('url', 'index.php?action=user');
			$smarty->assign('curStep', 3);
			
			$smarty->display('error.tpl');
		}
	}
	
	private static function success(Smarty $smarty) {
		$smarty->assign('heading', 'Installation finished');
		$smarty->assign('curStep', 4);
		
		$smarty->display('success.tpl');
	}
}

Install::run(Utils::getGET('action', ''));
?>
