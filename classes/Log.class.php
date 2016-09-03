<?php
if(!defined('E_DEPRECATED')) {
    define('E_DEPRECATED', 8192);
}

if(!defined('E_USER_DEPRECATED')) {
    define('E_USER_DEPRECATED', 16384); 
}

final class Log {
    /**
     * Konstruktor
     */
    private function __construct() { }
    
	/**
	 * Writes a new entry to the system log
	 * @static
	 * @param string Sender
	 * @param string Message
	 */   
    public static function sysLog($sender, $message) {
        $entry = new LogEntry();
		$entry->message	= $message;
		$entry->sender	= $sender;
		$entry->log		= 'system';
		
		$entry->save();
    }
	
    /**
     * Schreibt einen Fehler in die DB
     * @static
     * @param int|string Error-Level
     * @param string Fehlermeldung
     * @param string Datei, in der der Fehler aufgetreten ist
     * @param int Zeilennummer
     */
    private static function log($level, $message, $file, $line, $sender = NULL) {
       	$entry = new LogEntry();
		$entry->level	= self::level2string($level);
		$entry->message	= $message;
		$entry->file	= $file;
		$entry->line	= $line;
		$entry->sender	= $sender;
		$entry->log		= 'php';
		
		$entry->save();

        if(DEV_MODE) {
            echo $message."\n".$file.":".line;
        }
    }
    
    /**
     * Converts a PHP error level to string
     * @static
     * @param int Error level
     * @return string Error level as string
     */
    private static function level2string($level) {
        $levels = array(
            E_ERROR             => 'E_ERROR',
            E_WARNING           => 'E_WARNING',
            E_PARSE             => 'E_PARSE',
            E_NOTICE            => 'E_NOTICE',
            E_CORE_ERROR        => 'E_CORE_ERROR',
            E_CORE_WARNING      => 'E_CORE_WARNING',
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
            E_DEPRECATED        => 'E_DEPRECATED',
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
            E_USER_ERROR        => 'E_USER_ERROR',
            E_USER_WARNING      => 'E_USER_WARNING',
            E_USER_NOTICE       => 'E_USER_NOTICE',
            E_STRICT            => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR'
        );
        
        if(array_key_exists($level, $levels)) {
            return $levels[$level];
        } else if(is_string($level) && $level == 'EXCEPTION') {
            return 'EXCEPTION'; 
        } else if(is_string($level)) {
            return $level;
        }
        
        return 'UNKNOWN';
    }
    
    /**
     * Handles all kinds of PHP errors
     * @static
     * @param int|string Error level
     * @param string Error message
     * @param string File in which the error occured
     * @param int Line number
     */
    public static function handleError($level, $message, $file, $line) {
		Log::log($level, $message, $file, $line);
    }
    
    /**
     * Handles uncaught exceptions
     * @static
     * @param object Exception
     */
    public static function handleException(Exception $e, $uncaught = true) {
        $traceline  = "#%s %s@%s: %s(%s)";
        $trace      = array();

		foreach($e->getTrace() as $key => $value) {
			var_dump($e->getTrace());
        	
            $trace[]    = sprintf($traceline, $key, empty($value['file']) ? '' : $value['file'], empty($value['line']) ? '' : $value['line'], $value['function'], implode(', ', array_map("Log::var2string", $value['args'])));
        }
        
        Log::log('EXCEPTION', get_class($e) . ': '. $e->getMessage() . "\n\tTRACE: ".implode("\n\t\t", $trace), $e->getFile(), $e->getLine());
        if($uncaught)
			System::displayError('Uncaught exception.');
        if(DEV_MODE) {

        }
    }
	
	private static function var2string($var) {
		if(is_object($var)) {
			if(method_exists($var, "__toString")) {
				return (string)$var;
			} else {
				return get_class($var);
			}
		} else if(is_array($var)) {
			$var = array_map("Log::var2string", $var);
		} else {
			(string)$var;
		}
	} 
}

if(!DEV_MODE) {
	// Only add handlers when not in dev mode
	set_exception_handler(array('Log', 'handleException'));
	set_error_handler(array('Log', 'handleError'), E_ALL);
}
?>
