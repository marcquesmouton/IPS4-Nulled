<?php
/**
 * @brief		Installer bootstrap
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		2 Apr 2013
 * @version		SVN_VERSION_NUMBER
 */

require_once '../../init.php';
\IPS\Dispatcher\Setup::i()->setLocation('install')->run();