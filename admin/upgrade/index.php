<?php
/**
 * @brief		Upgrader bootstrap
 *
 * @copyright	(c) 2001 - SVN_YYYY Invision Power Services, Inc.
 *
 * @package		IPS Social Suite
 * @since		20 May 2014
 * @version		SVN_VERSION_NUMBER
 */

require_once '../../init.php';

if( \IPS\IN_DEV )
{
	die( "You must disable developer mode (IN_DEV) in order to run the upgrader" );
}

if( \IPS\NO_WRITES )
{
	die( "You must disable no-writes mode (NO_WRITES) in order to run the upgrader" );
}

if( \IPS\QUERY_LOG )
{
	die( "You must disable the query log (QUERY_LOG) in order to run the upgrader" );
}

\IPS\Dispatcher\Setup::i()->setLocation('upgrade')->run();