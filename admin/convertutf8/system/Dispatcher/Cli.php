<?php
/**
 * @brief		Dispatcher (CLI)
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Tools
 * @since		4 Sept 2013
 * @version		SVN_VERSION_NUMBER
 */

namespace IPSUtf8\Dispatcher;

/**
 * CLI Dispatcher
 */
class Cli extends \IPSUtf8\Dispatcher
{
	/**
	 * Run
	 */
	public function run()
	{
		$obj = new \IPSUtf8\modules\cli\cli;
		$obj->execute();
	}
	
	/**
	 * Init
	 */
	public function init()
	{
		
	}

	
}
