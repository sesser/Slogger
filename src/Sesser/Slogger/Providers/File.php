<?php
namespace Sesser\Slogger\Providers;
/**
 * File
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
 * A file based Logger
 *
 * @author randy sesser <sesser@gmail.com>
 * @copyright 2012, randy sesser
 * @license http://www.opensource.org/licenses/mit-license The MIT License
  */
class File extends \Sesser\Slogger\Slogger
{
	/**
	 * The logfile to which this logger... logs
	 * @var string 
	 */
	protected $logfile;
	/**
	 * The file handle as returned from fopen($this->logfile)
	 * @var resource 
	 */
	private $handle = NULL;

	/**
	 * Constructor
	 * 
	 * The constructor sets all the class properties and prepares the Slogger
	 * for logging (creates directories, corrects the path, etc)
	 * @param string $name The name of this Slogger
	 * @param array $config Configuration for this Slogger
	 * @throws \Sesser\Slogger\SloggerException 
	 */
	private final function __construct($name, array $config)
	{
		$this->name = $name;
		$this->config = $config;
		$this->enabled = $config['enabled'];
		$this->level = (int)$config['level'];
		$this->logfile = $config['logfile'];
		$this->dateFormat = $config['dateFormat'];
		$this->serializer = $config['serializer'];
		
		$this->checksum = static::GetChecksum($config);
		
		$path = dirname($this->logfile);
		$file = basename($this->logfile);
		$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$this->logfile = $path . $file;
		
		try {
			if (!is_dir($path))
				mkdir($path);
		} catch (\Exception $oex) {
			$this->enabled = false;
			throw new \Sesser\Slogger\SloggerException("Could not create directory '{$path}'. Logging has been disabled", 1001, $oex);
		}

		try {
			touch($this->logfile);
		} catch (\Exception $oex) {
			$this->enabled = false;
			throw new \Sesser\Slogger\SloggerException("Could not create file at '{$this->logfile}'. Logging has been disabled", 1001, $oex);
		}
	}
	
	/**
	 * Gets a Slogger by name and configures it
	 * 
	 * @param string $name The name of this Slogger [default: 'default']
	 * @param array $config The configuration for this Slogger
	 * @return \Sesser\Slogger\Providers\File
	 */
	public static function GetLogger($name = 'default', array $config = array())
	{
		$config = array_merge(array(
			'enabled'	=> false,
			'level'		=> self::LOG_LEVEL_ERROR,
			'logfile'	=> dirname(dirname(__FILE__)) . '/application.log',
			'serializer'=> NULL,
			'dateFormat'=> 'Y-m-d H:i:s'
		), $config);
		return new self($name, $config);
	}
	
	/**
	 * Writes the message and LOG_LEVEL to a file
	 * @param mixed $message The object to be logged
	 * @param int $level The LOG_LEVEL at which we're logging
	 */
	protected function log($message, $level = self::LOG_LEVEL_ERROR)
	{	
		if ($message instanceof \Exception) {
			$level = self::LOG_LEVEL_EXCEPTION;
			$log = sprintf('%s in %s [%d]', $message->getMessage(), $message->getFile(), $message->getLine());
			$trace = array();
			$stack = array_slice($message->getTrace(), 1);
			
			foreach ($stack as $t)
				$trace[] = sprintf('%sin %s %s%s%s() [%d]', "\t", $t['file'], $t['class'], $t['type'] == '' ? '->':$t['type'], $t['function'], $t['line']);
			
			$log .= PHP_EOL;
			$log .= implode(PHP_EOL, $trace);
			
			$message = $log;
		}
		
		//-- Build the base message
		$str_message = sprintf('[%s] %s - %s - ', date($this->dateFormat), $this->name, static::LogLevel($level));
		
		if (is_scalar($message)) {
			$str_message .= $message;
		} else {
			//-- if the serializer is callable, use it to "serialize" the object
			//-- else just use print_r()
			if (is_callable($this->serializer)) {
				$str_message .= call_user_func_array($this->serializer, array($message));
			} else {
				$str_message .= print_r($message, true);
			}
		}
		//-- add a newline character to the message
		$str_message .= PHP_EOL;
		//-- if the file handle is NULL, open the log for writing (append)
		if ($this->handle === NULL)
			$this->handle = fopen($this->logfile, 'a');

		if ($this->handle) {
			if (flock($this->handle, LOCK_EX)) {
				fwrite($this->handle, $str_message);
				fflush($this->handle);
				flock($this->handle, LOCK_UN);
			}
		}
	}
	
	public function __destruct()
	{
		try {
			if ($this->handle !== NULL && is_resource($this->handle)) {
				flock($this->handle, LOCK_UN);
				fclose($this->handle);
			}
		} catch(\Exception $ex) { }
	}
}
