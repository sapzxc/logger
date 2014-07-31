<?php
/**
 * Contains set of static logging methods
 * Please, use ONLY following priorities:
 *  - LOG_INFO
 *  - LOG_WARNING
 *  - LOG_DEBUG
 *  - LOG_ERR
 *
 * @method static void debug(string $msg)
 * @method static void info(string $msg)
 * @method static void warning(string $msg)
 * @method static void err(string $msg)
 */
class Logger {
	/**
	 * @var null|string
	 */
	private static $uid     = null;

	public static $logIndex = LOG_LOCAL0;

	public static $label    = '';

	public static $timer    = null;

	/**
	 * Log a message
	 * !WARNING! it log only first 1024 bytes of message
	 * @see http://php.net/syslog
	 * @param int $priority prioroty level
	 * @param string $message
	 */
	public static function log($priority, $message)
	{
		if(!self::$uid){ self::_init(); }
		syslog($priority, self::$label.$message);
	}

	/**
	 * Write big message to local data logs folder, much slower than syslog
	 * @param $priority
	 * @param $message
	 * @param string $section
	 */
	public static function file($priority, $message, $section='common')
	{
		// compose path
		$dirPath  = LOGS_PATH.'/'.$section.'/';
		$filePath = $dirPath.$section.'_'.date('Y_m_d').'.log';

		// compose message
		$client = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '(null)';

		$priorityName = 'LOG_UNKNOWN';
		switch($priority)
		{
			case LOG_DEBUG:		$priorityName = 'LOG_DEBUG'; break;
			case LOG_INFO:		$priorityName = 'LOG_INFO'; break;
			case LOG_WARNING:	$priorityName = 'LOG_WARNING'; break;
			case LOG_ERR:		$priorityName = 'LOG_ERR'; break;
		}

		// add text block wrapper for multi line output
		if(strpos($message,"\n")!==false)
		{
			$id = 'B'.date('Ymd_His').'_'.substr(microtime(),2,4);
			$message = '<<<'.$id."\n".$message."\n".$id."\n";

		}
		$message = "[".date('Y-m-d H:i:s')."]\t$client\t$priorityName\t$message";

		// write
		if(!is_dir($dirPath))
		{
			umask(0);
			if(!mkdir($dirPath, 0777, true))
			{
				Logger::log(LOG_ERR, "LOG\tCreate dir failed\t[$dirPath]");
				error_log($message);
				return;
			}
		}

		error_log($message."\n", 3, $filePath);
	}

	/**
	 * @static
	 * @param $priority
	 * @param $message
	 * @return mixed
	 */
	public static function timer($priority, $message)
	{
		if(empty(self::$timer))
		{
			self::$timer = microtime(1);
			self::log($priority, $message);
			return;
		}

		self::log($priority, sprintf("%0.4f\t",microtime(1)-self::$timer).$message);
		self::$timer = microtime(1);
	}

	/**
	 * Logging initialization
	 */
	private static function _init()
	{
		if(!self::$uid){ self::$uid = uniqid(); }
		openlog(self::$uid, LOG_ODELAY, self::$logIndex);
	}

	/**
	 * Shortcut for Logger::log(LOG_*, '')
	 * @static
	 * @param $name
	 * @param $arguments
	 */
	public static function __callStatic($name, $arguments)
	{
		$level = 'LOG_'.strtoupper($name);

		if(defined($level))
		{
			$msg = isset($arguments[1])
				? $arguments[1]."\t".$arguments[0]
				: $arguments[0];

			self::log(constant($level), $msg);
		}
	}
}
