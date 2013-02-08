<?php
namespace Sesser\Slogger\Providers;
/**
 * Mongodb Provider file
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
 * Description of Mongodb
 *
 * @author randy sesser <sesser@gmail.com>
 * @copyright 2012, randy sesser
 * @license http://www.opensource.org/licenses/mit-license The MIT License
 */
class Mongodb extends \Sesser\Slogger\Slogger
{
	/**
	 * A Mongo DB object
	 * @var \MongoDB 
	 */
	private $db;
	/**
	 * The Mongo collection in which logs are inserted
	 * @var \MongoCollection 
	 */
	private $collection;
	/**
	 * The server config for this mongo logger
	 * @var array 
	 */
	protected $server;
	
	/**
	 * Constructor initializes propeties and connects to Mongo 
	 * @param string $name The name of this Logger
	 * @param array $config The configuration for this Logger
	 * @throws \Sesser\Slogger\SloggerException 
	 */
	private final function __construct($name, array $config)
	{
		
		$this->config = $config;
		$this->enabled = $config['enabled'];
		$this->level = (int)$config['level'];
		$this->name = $name;
		$this->server = $config['serializer'];
		$this->dateFormat = $config['dateFormat'];
		$this->server = $config['server'];
		
		$this->server['dsn'] = rtrim($this->server['dsn'], '/');
		
		$this->checksum = static::GetChecksum($config);
		
		$mongo = NULL;
		try {
			$mongo = new \Mongo($this->server['dsn'], $this->server['options']);
		} catch (\MongoConnectionException $mex) {
			$dsn = parse_url($this->server['dsn']);
			$error = "Could not connect to Mongo at " . $dsn['scheme'] . '://';
			if (!empty($dsn['user'])) {
				$error .= $dsn['user'];
				if (!empty($dsn['pass'])) {
					$error .= ':*******';
				}
				$error .= '@';
			}
			$error .= $dsn['host'];
			$error .= ':' . (!empty($dsn['port']) ? $dsn['port']:'27017');
			$this->enabled = false;
			throw new \Sesser\Slogger\SloggerException($error, 1002, $mex);
		}
		if ($mongo !== NULL) {
			$this->db = $mongo->selectDB($this->server['db']);
			$this->collection = $this->db->selectCollection($this->name);
		} else {
			throw new \Exception("Could not connect to Mongo");
		}
	}
	
	/**
	 * Gets an instance of a Mongodb Logger by name and configures it
	 * @param string $name The name of the logger
	 * @param array $config The configuration for the logger 
	 * @return Mongodb 
	 */
	public static function GetLogger($name, array $config)
	{
		$config = array_merge(array(
			'enabled'	=> false,
			'level'		=> self::LOG_LEVEL_ERROR,
			'server'	=> array(),
			'serializer' => NULL,
			'dateFormat' => 'Y-m-d H:i:s'
		), $config);
		$config['server'] = array_merge(array(
			'dsn'		=> 'mongodb://127.0.0.1:27017',
			'db'		=> 'applog',
			'options'	=> array(
				'timeout' => 1000
			)
		), $config['server']);

		return new self($name, $config);
	}
	
	/**
	 * Logs a message to Mongodb
	 * @param mixed $message The object to log
	 * @param int $level The level of the log event
	 */
	protected function log($message, $level = self::LOG_LEVEL_ERROR)
	{
		
		if ($message instanceof \Exception) {
			$level = self::LOG_LEVEL_EXCEPTION;
			$slog = sprintf('%s in %s [%d]', $message->getMessage(), $message->getFile(), $message->getLine());
			$trace = array();
			$stack = array_slice($message->getTrace(), 1);
			
			foreach ($stack as $t)
				$trace[] = sprintf('%sin %s %s%s%s() [%d]', "\t", $t['file'], $t['class'], $t['type'] == '' ? '->':$t['type'], $t['function'], $t['line']);
			
			$slog .= PHP_EOL;
			$slog .= implode(PHP_EOL, $trace);
			
			$message = $slog;
		}
		
		$record = array(
			'date'	=> new \MongoDate(),
			'level'	=> static::LogLevel($level)
		);
		
		if (is_scalar($message)) {
			$record['message'] = $message;
		} else {
			if (is_callable($this->serializer)) {
				$record['message'] = call_user_func_array($this->server, array($message));
			} else {
				$record['message'] = print_r($message, true);
			}
		}
		$this->collection->insert($record);
		
	}
}
