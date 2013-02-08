#Sesser\Slogger\Slogger#

[![Build Status](https://secure.travis-ci.org/sesser/Slogger.png?branch=master)](http://travis-ci.org/sesser/Slogger)

This is a basic logging utility. It supports a file based logger and a [MongoDB](http://www.mongodb.org)
based logger. Each log provider has different settings which I'll go over below.
The way this util is set up should make it easy to implement whatever provider
your project requires (another database, a different NoSQL store, a web service, etc).

##Features & Goodies##

*	Easy to configure and use
*	Multiple log levels. DEBUG, INFO, WARN, ERROR
*	Log exceptions with stacktrace. Just pass an Exception to a Slogger method.
*	Can be re-configured the middle of your app (see below)
*	Create multiple Loggers with different Providers/settings
*	Easily implement any Provider you want (Mongodb, SQL, Memcacheq, Beanstalkd, Redis, etc)
*	It's fast, Unit Tested, and well documented

##Sesser\Slogger\Providers\File##

The File provider is pretty basic. It supports a few configuration options as
described below:

The default config looks like this:
	
```php
<?php
$config = array(
	'enabled'	 => false,
	'logfile'	 => dirname(dirname(__FILE__)) . 'tmp/logs/application.log',
	'level'		 => \Sesser\Slogger\Slogger::LOG_LEVEL_ERROR,
	'serializer' => NULL,
	'dateFormat' => 'Y-m-d H:i:s'
);
```

Most of those are pretty self explanatory. You'll notice that it defaults to `ISlogger::LEVEL_ERROR`
and is disabled. The `logfile` setting is the path to the actual log file. The logging
util is built as a singleton but you can create multipe file loggers by specifying
a different logfile when you call `Slogger::Get('MyLogger')`

The `serializer` setting is there to provide you with your own *serializer* for the 
objects that you log. By default, if you pass anything other than a string, Slogger
uses `print_r($obj, true)`. This setting must be callable (via `is_callable()`) or 
it won't work.

### Setting up a simple File logger ###

Here's how to doit...

```php
<?php

$serializer = function($obj) {
	if (is_string($obj))
		return $obj;
	return json_encode($obj, true);
};
//-- Somewhere in your application...
Slogger::Configure('MyLogger', array(
	'provider' => 'File',
	'settings' => array(
		'enabled' 	=> true,
		'level'		=> \Sesser\Slogger\Slogger::LOG_LEVEL_DEBUG,
		'logfile' 	=> '/tmp/myapp.log',
		'serializer'=> $serializer
)));

//-- When you need your logger... get it
$log = Logger::Get('MyLogger');

$log->Debug("This is a debug message");
$log->Error("Exception caught! See below");
$log->Error($ex);
```

If you want to get the same logger in another class, you don't have to pass all the same
settings when you called it the first time. Just pass the `logfile` setting and Logger
will find the correct logger you're looking for.

```php
<?php
$log = Slogger::Get('MyLogger');
```

If you want to change the way the logger is configured (say you want to programmatically 
turn on debug or disable logging altogether), simply call configure with your updated
configuration array and it willl automatically pickup the changes the next time you call
`$log = Slogger::Get('MyLogger');`

##Sesser\Slogger\Providers\Mongodb##

**NB:** This hasn't been implemented yet. But will be shortly.

The Mongodb provider was born out of curiosity more than any need. I first thought
about a regular DB (like MySQL) but I'm not sure anyone wants their app bound
to database writes when things go south and app starts logging errors all frantic
like because of an edge case. Anyway, the configuration for the Mongo provider
is a little different but does share some similar settings.

```php
<?php
	$config = array(
		'enabled'	=> false,
		'level'		=> \Sesser\Slogger\Slogger::LOG_LEVEL_ERROR,
		'server'	=> array(
			'dsn'		=> 'mongodb://127.0.0.1:27017',
			'db'		=> 'applog',
			'options'	=> array(
				'timeout' => 1000
			)
		),
		'serializer' => NULL
	);
```

The `name` setting here is kinda like the `logfile` setting in the File logger. It provides
a unique identifier for a particular logger (e.g. you can have multiple loggers in one app).
The `server` config array is basic all what gets passed to the [Mongo](http://us.php.net/manual/en/mongo.connecting.php)
constructor so make sure these settings are correct. The `server -> options` array is optional
but there if you need it. If you need to override any of the defaults (or add your own), just
pass in an empty array for the options (or add keys that you need).

##Other tid-bits##

This project as some test cases and all the ones I've written pass. That doesn't mean
it's inclusive of all scenarios and use-cases. If you find a particular use-case, please
either update the tests or file an issue here on GitHub.

This project also uses the new(er) [ApiGen](http://apigen.org/). To generate the API docs, simply
navigate to the `src` directory in a terminal and type `apigen`. This assumes
you have ApiGen installed correctly and that `apigen` is in your path.

