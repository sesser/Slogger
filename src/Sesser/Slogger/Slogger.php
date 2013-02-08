<?php
namespace Sesser\Slogger;

/**
 * Slogger
 * 
 * Copyright (c) 2012 randy sesser <sesser@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * @author randy sesser <sesser@gmail.com>
 * @copyright 2012, randy sesser
 * @license http://www.opensource.org/licenses/mit-license The MIT License
 * @version 1.0.2
 * @filesource
 */

/**
 * Logger abstract class
 *
 * @author randy sesser <sesser@gmail.com>
 * @copyright 2012, randy sesser
 */
abstract class Slogger implements ISlogger
{
	/**
	 * Enabled property
	 * @var boolean 
	 */
	protected $enabled;
	/**
	 * The LOG_LEVEL
	 * @var int 
	 */
	protected $level;
	/**
	 * The name of the logger
	 * @var string 
	 */
	protected $name;
	/**
	 * The callback used for outputting objects to the log
	 * @var mixed 
	 */
	protected $serializer;
	/**
	 * The configuration for this Logger
	 * @var array 
	 */
	protected $config;
	/**
	 * The date format passed to the date() function to output a timestamp
	 * @var string 
	 */
	protected $dateFormat = 'Y-m-d H:i:s';
	/**
	 * The checksum for the current Logger's configuration
	 * @var string
	 */
	protected $checksum;
	/**
	 * An array of checksums that point point to configs. If one changes, we know
	 * to refresh the Logger for this config
	 * @var array 
	 */
	protected static $checksums;
	/**
	 * Configs for our Loggers. Indexed by Logger name
	 * @var array 
	 */
	private static $configs;
	/**
	 * Instantiated Loggers array so we can have multiple Loggers going in the 
	 * same application. Indexed by Logger name
	 * @var array 
	 */
	private static $loggers;
		
	/**
	 * Gets an ILogger by the name $name. You must configure a Logger first before
	 * calling this method. Logger::Configure($name, array $settings);
	 * 
	 * @param string $name The name of the logger you wish to get
	 * @return ILogger
	 * @throws SloggerException 
	 */
	public static function get($name)
	{
		$config_key = sha1($name);
		//-- if there are no configs or, at least, none for this logger, throw an exception
		if (!is_array(static::$configs) || !array_key_exists($config_key, static::$configs))
			throw new SloggerException("No configuration for logger '{$name}' could be found. Make sure you call Logger::Configure('name', array \$settings) first.");
			
		//-- pull the config from the configs array
		$config = static::$configs[$config_key];
		
		//-- build the logger key
		$logger_key = $config_key . '_' . strtolower($config['provider']);
		
		/** @type Logger */
		$logger = NULL;
		
		//-- get our logger from the loggers array (if it exists)
		if (is_array(static::$loggers) && array_key_exists($logger_key, static::$loggers))
			$logger =  static::$loggers[$logger_key];
		
		//-- if we have a logger and the config checksums match, return it. It's ready for logging
		if ($logger !== NULL && $logger->checksum == static::GetChecksum($config['settings']))
			return $logger;
		
		if (!is_array(static::$loggers))
			static::$loggers = array();
		
		//-- build our provider class
		$class = sprintf('%s\Providers\%s', __NAMESPACE__, ucfirst($config['provider']));
		
		//-- call the 'GetLogger' method on our provider class; passing name and settings
		$logger = call_user_func_array(array($class, 'GetLogger'), array($name, $config['settings']));
		
		//-- "cache" this logger for later retreival
		static::$loggers[$logger_key] = $logger;
		
		//-- return the new logger
		return $logger;
	}
	
	/**
	 * Adds a configuration for a Logger. Call Logger::Get($name) after this method
	 * to get your Logger.
	 * 
	 * @param string $name The name of the Logger you want to configure
	 * @param array $config The settings passed to the Log Provider
	 * @return void
	 */
	public static function configure($name, array $config = array())
	{
		$config_key = sha1($name);
		if (!is_array(static::$configs))
			static::$configs = array();
		//-- store the config in a static array
		static::$configs[$config_key] = array_merge(array(
			'provider'	=> 'File',
			'settings'	=> array(
				'enabled'	=> false,
				'level'		=> self::LOG_LEVEL_ERROR,
				'logfile'	=> dirname(__FILE__) . '/application.log',
				'serializer'=> NULL,
				'dateFormat'=> 'Y-m-d H:i:s'
			)
		), $config);
		
		if (!is_array(static::$checksums))
			static::$checksums = array();
		//-- store the checksum for the config for validation
		static::$checksums[$config_key] = static::GetChecksum(static::$configs[$config_key]);
	}
	
	/**
	 * Check if Debug is enabled
	 * @return boolean 
	 */
	public function IsDebugEnabled()
	{
		return $this->enabled && $this->level <= self::LOG_LEVEL_DEBUG;
	}
	
	/**
	 * Check if Info is enabled
	 * @return boolean 
	 */
	public function IsInfoEnabled()
	{
		return $this->enabled && $this->level <= self::LOG_LEVEL_INFO;
	}
	
	/**
	 * Check if Warn is enabled
	 * @return boolean 
	 */
	public function IsWarnEnabled()
	{
		return $this->enabled && $this->level <= self::LOG_LEVEL_WARN;
	}
	
	/**
	 * Check if Error is enabled
	 * @return boolean 
	 */
	public function IsErrorEnabled()
	{
		return $this->enabled && $this->level <= self::LOG_LEVEL_ERROR;
	}
	
	/**
	 * Log a DEBUG message to a file
	 * @param mixed $message The object you want to log
	 */
	public function debug($message)
	{
		if ($this->IsDebugEnabled())
			$this->log($message, self::LOG_LEVEL_DEBUG);
	}

	/**
	 * Log an ERROR message to a file
	 * @param mixed $message The object you want to log
	 */
	public function error($message)
	{
		if ($this->IsErrorEnabled())
			$this->log($message, self::LOG_LEVEL_ERROR);
	}

	/**
	 * Log an INFO message to a file
	 * @param mixed $message The object you want to log
	 */
	public function info($message)
	{
		if ($this->IsInfoEnabled())
			$this->log($message, self::LOG_LEVEL_INFO);
	}

	/**
	 * Log a WARN message to a file
	 * @param mixed $message The object you want to log
	 */
	public function warn($message)
	{
		if ($this->IsWarnEnabled())
			$this->log($message, self::LOG_LEVEL_WARN);
	}
	
	/**
	 * The log method does all the work and is implemented in the provider
	 * 
	 * @param mixed $message An object to log
	 * @param int $level The log level of this message
	 */
	protected abstract function log($message, $level);


	/**
	 * Get the string value of a log level (e.g. DEBUG)
	 * @param int $level The numerical value for a logger
	 * @return string 
	 */
	protected static function logLevel($level)
	{
		$ref = new \ReflectionClass('Sesser\Slogger\ISlogger');
		$levels = $ref->getConstants();
		foreach ($levels as $name => $value)
			if ($value === $level)
				return str_replace('LOG_LEVEL_', '', $name);
		
		return 'UKNOWN';
	}
	
	/**
	 * Builds an sha1 hash from the configuration array
	 * @param array $config The configuration array to checksum
	 * @return string 
	 */
	protected static function getChecksum($config)
	{
		return sha1(json_encode($config));
	}
}
