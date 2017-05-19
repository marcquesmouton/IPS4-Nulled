<?php
/**
 * @brief		Public bootstrap
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		18 Feb 2013
 * @version		SVN_VERSION_NUMBER
 */
$_SERVER['SCRIPT_FILENAME']	= __FILE__;
require_once 'init.php';
\IPS\Dispatcher\Front::i()->run();