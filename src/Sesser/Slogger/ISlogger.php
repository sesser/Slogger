<?php
namespace Sesser\Slogger;
/**
 * ISlogger
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
 * ILogger interface
 * @author randy sesser <sesser@gmail.com>
 * @copyright 2012, randy sesser
 * @license http://www.opensource.org/licenses/mit-license The MIT License
 */
interface ISlogger
{
	/**
	 * Log level DEBUG constant 
	 */
	const LOG_LEVEL_DEBUG = 0;
	/**
	 * Log level INFO constant 
	 */
	const LOG_LEVEL_INFO = 1;
	/**
	 * Log level WARN constant 
	 */
	const LOG_LEVEL_WARN = 2;
	/**
	 * Log level ERROR constant 
	 */
	const LOG_LEVEL_ERROR = 3;
	/**
	 * Log level EXCEPTION constant 
	 */
	const LOG_LEVEL_EXCEPTION = 4;
	/**
	 *  Is debug logging enabled?
	 */
	public function IsDebugEnabled();
	/**
	 *  Is info logging enabled?
	 */
	public function IsInfoEnabled();
	/**
	 *  Is warning logging enabled?
	 */
	public function IsWarnEnabled();
	/**
	 *  Is error logging enabled?
	 */
	public function IsErrorEnabled();
	

	/**
	 * Log a DEBUG message
	 * @param mixed $message An object to log
	 */
	public function Debug($message);
	/**
	 *  Log an INFO message
	 * @param mixed $message An object to log
	 */
	public function Info($message);
	/**
	 *  Log a WARN message
	 * @param mixed $message An object to log
	 */
	public function Warn($message);
	/**
	 *  Log an ERROR message
	 * @param mixed $message An object to log
	 */
	public function Error($message);
}
